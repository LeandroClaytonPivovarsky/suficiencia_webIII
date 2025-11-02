<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    function register(Request $request) {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|min:8|confirmed'
        ]);


        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password'])
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['status' => true, 'user' => $user, 'token' => $token]);

    }

    function login(Request $request) {
        $validatedData = $request->validate([
            'email' => 'required|email:rfc,dns',
            'password' => 'required|min:8'
        ]);

        if (Auth::attempt($validatedData)) {
            $user = User::where('email', $validatedData['email'])->firstOrFail();

          $token = $user->createToken('api-token')->plainTextToken;

          return response()->json(['status' => true, 'message' => 'Logado com sucesso!', 'data' => ['token' => $token]]);
        }

        return response()->json(['status' => false, 'msg' => 'Credenciais inválidas'], 401);

    }

    function logout(Request $request) {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['status' => false, 'msg' => 'Token não informado'], 400);
        }

        $access_token = PersonalAccessToken::findToken($token);

        if (!$access_token) {
            return response()->json(['status' => false, 'msg' => 'Token inválido'], 400);
        }

        $access_token->delete();

        return response()->json(['status' => true, 'msg' => 'Logout realizado!']);
    }

}
