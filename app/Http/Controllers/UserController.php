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
            'name' => 'nullable|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ];

        $messages = [
            'name.unique' => 'The name already exists.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email field is not valid.',
            'email.unique' => 'The email is already registered.',
            'password.min' => 'The password must contain a minimum of 8 characters.',
            'password.required' => 'The password field is required.',

        ];
        $validator = Validator::make($request->all(), $validation, $messages);
        
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $name = $request->filled('name') ? $request->name : 'Anonymous';

        $user = User::create([
            'name' => $name,
            'email' => $request-> email,
            'password' => Hash::make($request->password),
        ])->assignRole('player');

        return response()->json(['message' => 'Register completed'], 201); 
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
        $newName = $request-> input('name');

        if ($user->id !== Auth::user()->id) {
            return response()->json([
                'message' => 'Cant update the name.'], 401);
        } 
        if(empty($newName)){
            return response()->json([
                'error' => 'This field is required.'], 422);
        }

        if ($newName !== $user->name){
            
            $validation = [
                'name' => 'unique:users',
            ];

    
            $validator = Validator::make($request->only('name'), $validation);
            
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }

            $user->name = $newName;
            $user->save();

            return response()->json(['message' => 'The name has been change.',], 200);
        } else{

            return response()->json(['message' => 'Try a different name, please.',], 422);
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
                'Name' => $user->name,
                'E-mail' => $user->email,
                'winnig rate' => $winnigRate,
            ];
        });
    
        return response()->json(['users' => $users], 200);
    }

    public function ranking(){
    
        $players = User::whereHas('roles', function ($query) {
            $query->where('name', 'player');
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
                'name' => $player->name,
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
                $query->where('name', 'player');
            })->withCount(['games', 'games as wins_count' => function ($query) {
                $query->where('result', true);
            }])->get()->sortByDesc(function ($user) {
                return $user->games_count ? $user->wins_count / $user->games_count : 0;
            })->first();
    
            if ($winner) {
                $game_won = $winner->wins_count;
                $wins_rate = $winner->games_count ? round(($game_won / $winner->games_count) * 100, 2) : 0;
    
                return response()->json([
                    'name' => $winner->name,
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
                $query->where('name', 'player');
            })->withCount(['games', 'games as wins_count' => function ($query) {
                $query->where('result', true);
            }])->get()->sortBy(function ($user) {
                return $user->games_count ? $user->wins_count / $user->games_count : 0;
            })->first();
    
            if ($loser) {
                $game_won = $loser->wins_count;
                $wins_rate = $loser->games_count ? round(($game_won / $loser->games_count) * 100, 2) : 0;
    
                return response()->json([
                    'name' => $loser->name,
                    'Wins rate' => $wins_rate,
                    'Wins' => $game_won,
                    'Total Games' => $loser->games_count
                ], 200);
            } else {
                return response()->json(['message' => 'There is no loser'], 404);
            }
        }
}