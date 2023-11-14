<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nickname' => $this->faker->unique()->word,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('1234567890'),
        ];
    }
}

User::created(function (User $user) {
    $role = Role::where('name', 'player')->first();
    $user->assignRole($role);
 });
?>