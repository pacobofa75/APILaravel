<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;



class UserController extends Controller
{
    public function register(Request $request){
        
        $validation = [
            'nickname' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ];

        
        $validator = Validator::make($request->all(), $validation);
        
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        // If no nickname is provided then assign a default "Anonymous"
        $nickname = $request->filled('nickname') ? $request->nickname : 'Anonymous';

        $user = User::create([
            'nickname' => $nickname,
            'email' => $request-> email,
            'password' => Hash::make($request->password),
        ])->assignRole('player');

        return response()->json(['message' => 'Registration completed'], 201); 
    }
    
    public function login (Request $request){
        
        $validation = [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $messages = [
            
            'email.required' => 'The email field is required.',
            'email.email' => 'The email field is not valid.',
            'password.required' => 'The password field is required.',
        ];
        $validator = Validator::make($request->all(), $validation, $messages);
        
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }; 

        $login_data =[
            'email' => $request-> email,
            'password' => $request-> password,
        ];

        if(Auth::attempt($login_data)) {
            $user = Auth::user();
            /** @var \App\Models\User $user **/ 
            $token = $user->createToken('authUserToken')->accessToken;
        
            return response()->json([
                'message' => 'Logged in successfull!',
                'user' => $user,
                'token' => $token, 
            ], 200);
        } else {
            // Las credenciales de inicio de sesión son incorrectas
            return response()->json([
                'message' => 'Incorrect information',
            ], 401);
        }

    }
    
    public function update(Request $request, $id){
     
        $user = User::find($id); 
        $newNickName = $request-> input('nickname');

        if ($user->id !== Auth::user()->id) {
            return response()->json([
                'message' => 'Cant update the nickname.'], 401);
        } 
        if(empty($newNickName)){
            return response()->json([
                'error' => 'This field is required.'], 422);
        }

        if ($newNickName !== $user->nickname){
            
            $validation = [
                'nickname' => 'required|string|max:255',
            ];

    
            $validator = Validator::make($request->only('nickname'), $validation);
            
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }

            $user->nickname = $newNickName;
            $user->save();

            return response()->json(['message' => 'The nickname has been change.',], 200);
        } else{

            return response()->json(['message' => 'Try a different nickname, please.',], 422);
        }
    }

    public function logout(){
      
        /** @var \App\Models\User $user **/
        $user = Auth::user();

        $token = $user->token();
        $token->revoke();

        return response()->json(['message' => 'You have Log out!'], 200);

    }
    
    public function listPlayers() {
        $users = User::all()->map(function ($user) {
            $totalGames = $user->games->count();
            $totalWins = $user->games->where('result', 1)->count();
    
            
            $winnigRate = $totalGames > 0 ? ($totalWins / $totalGames) * 100 : 0;
    
            return [
                'Id' => $user->id,
                'Nickname' => $user->nickname,
                'E-mail' => $user->email,
                'winnig rate' => $winnigRate,
            ];
        });
    
        return response()->json(['users' => $users], 200);
    }

    public function ranking(){
    
        $players = User::whereHas('roles', function ($query) {
            $query->where('nickname', 'player');
        })->withCount(['games', 'games as wins_count' => function ($query) {
            $query->where('result', true);
        }])->get();
    
        if ($players->isEmpty()) {
            return response()->json(['message' => 'There are no players'], 404);
        }
    
        $playersData = $players->map(function ($player) {
            $game_won = $player->wins_count;
            $wins_rate = $player->games_count ? round(($game_won / $player->games_count) * 100, 2) : 0;
    
            return [
                'Nickname' => $player->nickname,
                'Wins rate' => $wins_rate,
                'Wins' => $game_won,
                'Total Games' => $player->games_count
            ];
        });

        $ranking = $playersData->sortByDesc('Wins');
    
        return response()->json($ranking->values()->all(), 200);
    }

    public function winner() {
        
            $winner = User::whereHas('roles', function ($query) {
                $query->where('nickname', 'player');
            })->withCount(['games', 'games as wins_count' => function ($query) {
                $query->where('result', true);
            }])->get()->sortByDesc(function ($user) {
                return $user->games_count ? $user->wins_count / $user->games_count : 0;
            })->first();
    
            if ($winner) {
                $game_won = $winner->wins_count;
                $wins_rate = $winner->games_count ? round(($game_won / $winner->games_count) * 100, 2) : 0;
    
                return response()->json([
                    'nickname' => $winner->nickname,
                    'Wins rate' => $wins_rate,
                    'Wins' => $game_won,
                    'Total Games' => $winner->games_count
                ], 200);
            } else {
                return response()->json(['message' => 'There is no winner'], 404);
            }
    }
    
    public function loser() {

            $loser = User::whereHas('roles', function ($query) {
                $query->where('nickname', 'player');
            })->withCount(['games', 'games as wins_count' => function ($query) {
                $query->where('result', true);
            }])->get()->sortBy(function ($user) {
                return $user->games_count ? $user->wins_count / $user->games_count : 0;
            })->first();
    
            if ($loser) {
                $game_won = $loser->wins_count;
                $wins_rate = $loser->games_count ? round(($game_won / $loser->games_count) * 100, 2) : 0;
    
                return response()->json([
                    'nickname' => $loser->nickname,
                    'Wins rate' => $wins_rate,
                    'Wins' => $game_won,
                    'Total Games' => $loser->games_count
                ], 200);
            } else {
                return response()->json(['message' => 'There is no loser'], 404);
            }
        }
}