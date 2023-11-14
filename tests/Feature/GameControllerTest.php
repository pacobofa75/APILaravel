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
use Illuminate\Foundation\Testing\RefreshDatabase;


class GameControllerTest extends TestCase
{
  
    public function test_new_game()
    {
        $player = User::factory()->create();
        $player->assignRole('player');
        Passport::actingAs($player);
    
        $response = $this->post('/api/players/' . $player->id . '/games');
    
        $response->assertStatus(200);
    
        $response->assertJsonStructure([
            'message',
            'dice1',
            'dice2',
            'result',
        ]);
    }
    public function testDeleteGames () {

        $user = User::factory()->create();

        $user->assignRole('player');

        Game::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->deleteJson("/api/players/{$user->id}/games");

        $response->assertStatus(200);
        $response->assertJson([

                'message' => "You have deleted all your games"
                
        ]);
    }
}
