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
        $verification_code = rand(100000, 999999); // 6-digit numeric code

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'verification_code' => $verification_code,
        ]);

        $emailBody = "Welcome to our platform, {$user->name}!\n\n";
        $emailBody .= "Your activation code is: {$verification_code}\n";
        $emailBody .= "Please use this code to verify your account.\n\n";
        $emailBody .= "Thank you!";

        // $result = sendEmail($user->email, 'Account Activation', $emailBody);
        $result = sendEmail('chirovemunyaradzi@gmail.com', 'Account Activation', $emailBody);


        if ($result === true) {
            return response()->json([
                'message' => 'User registered successfully. Check your email for the activation code.',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ], 201);
        } else {
            return response()->json([
                'message' => 'User registered, but failed to send email.',
                'error' => $result
            ], 500);
        }
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
