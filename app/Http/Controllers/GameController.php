<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Game;


class GameController extends Controller{


    public function throwDice() {
        $authUser = Auth::user(); 
    
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        
        $result = ($dice1 + $dice2) === 7 ? true : false;
    
        $game = new Game();
        $game->user_id = $authUser->id;
        $game->dice1 = $dice1;
        $game->dice2 = $dice2;
        $game->result = $result;
        $game->save();
        
        return response()->json([
            'message' => 'You rolled the dice!',
            'dice1' => $dice1,
            'dice2' => $dice2,
            'result' => $result ? "You won!!" : "You lost, Try again!!",
        ], 200);
    }
    public function listGames(){
        
        $authUser = Auth::user();
        
        $games = $authUser->games;

            if ($games->count() > 0) {
                $listGames = [];
                foreach ($games as $game) {
                   
                    $result = $game->result ? 'You won' : 'You lost';

                    $AllGames = [
                        'first dice' => $game->dice1,
                        'second dice' => $game->dice2,
                        'result' => $result,
                    ];

                    $listGames[] = $AllGames;
                }

                return response()->json([
                    'Your Plays' => $listGames,
                    'result'=> $authUser->result,
                
                ], 200);
            } else {
                return response()->json(['message' => 'Not games here yet.'], 202);
            }
    }

    public function destroy()
{
    $authID = Auth::id();
    
    $user = User::find($authID);

    if ($user->games()->count() > 0) {
        $user->games()->delete();
        return response()->json(['message' => 'You have erased all your games'], 200);
    } else {
        return response()->json(['message' => 'You havent played yet'], 204);
    }
}
}