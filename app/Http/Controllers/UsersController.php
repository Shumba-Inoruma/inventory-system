<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    // Registration endpoint
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'name' => 'required|string'
        ]);

        // Ensure viewer role exists
        $role = Role::firstOrCreate(['name' => 'viewer']);

        // Generate verification code
        $verification_code = Str::random(6);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'verification_code' => $verification_code,
        ]);

        // Send verification email
        Mail::raw("Your activation code is: $verification_code", function($message) use ($user) {
            $message->to($user->email)
                    ->subject('Activate Your Account');
        });

        return response()->json([
            'message' => 'User registered. Check your email for activation code.'
        ]);
    }

    // Activation endpoint
    public function activate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required'
        ]);

        $user = User::where('email', $request->email)
                    ->where('verification_code', $request->verification_code)
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid code or email'], 400);
        }

        $user->is_verified = true;
        $user->verification_code = null;
        $user->save();

        return response()->json(['message' => 'Account activated successfully!']);
    }
}
