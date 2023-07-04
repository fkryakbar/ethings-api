<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:30'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:3', 'confirmed']
        ]);
        $request->merge([
            'user_id' => Str::uuid(),
            'role' => 'user',
            'password' => bcrypt($request->password)
        ]);
        $user = User::create($request->except(['_token', 'password_confirmation']));

        return response([
            'message' => 'Account Created',
            'data' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:3']
        ]);
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response([
            'message' => 'Login Success',
            'access_token' => $user->createToken('access_token')->plainTextToken,
            'data' => $user
        ]);
    }

    public function active_user(Request $request)
    {
        return response([
            'message' => 'User is Active',
            'data' => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        $user = User::where('user_id', $request->user()->user_id)->first();
        if ($user) {
            $user->tokens()->delete();
        }

        return response([
            'message' => 'logout successfully'
        ]);
    }
}
