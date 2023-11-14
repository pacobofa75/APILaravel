<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Game; 


class UserControllerTest extends TestCase
{

    use DatabaseTransactions;

        /**
     * Registration test
     */

    public function test_register_is_correct(): void
    {
        $response = $this->postJson('/api/register',[
            'nickname' => 'francisco',
            'email' => 'nick@example.com',
            'password' => '123456789',

        ]);    

        $response->assertStatus(201);
    }
    
    public function test_update_nickname () {

        // Create a user
        $user = User::factory()->create();
        $user->assignRole('player'); 

        $newNickname = 'newNickname';

        // Act
        User::where('id', $user->id)->update(['nickname' => $newNickname]);
        $response = $this->actingAs($user, 'api')->putJson('/api/players/{$user->id}', ['newNickname' => $newNickname]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Nickname update completed']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'nickname' => $newNickname]);

    }
    public function test_winner(){

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Passport::actingAs($admin);

        $response = $this->get('/api/players/ranking/winner');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'nickname',
            'Wins rate',
            'Wins',
            'Total Games',
        ]);
    }
    public function test_loser(){

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Passport::actingAs($admin);

        $response = $this->get('/api/players/ranking/loser');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'nickname',
            'Wins rate',
            'Wins',
            'Total Games',
        ]);
    }
}
