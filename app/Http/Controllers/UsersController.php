<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Log; 

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
         // Log to MongoDB
       
        $emailBody = "Welcome to our platform, {$user->name}!\n\n";
        $emailBody .= "Your activation code is: {$verification_code}\n";
        $emailBody .= "Please use this code to verify your account.\n\n";
        $emailBody .= "Thank you!";

        // $result = sendEmail($user->email, 'Account Activation', $emailBody);
        $result = sendEmail('chirovemunyaradzi@gmail.com', 'Account Activation', $emailBody);


        if ($result === true) {
            Log::create([
            'user_id' => auth()->id() ?? null,
            'action' => 'register_user',
            'details' => "New user registered: 
                Name - {$user->name}, 
                Email - {$user->email}, 
                Role - {$role->name}, 
                Verification Code - {$verification_code}",
            'type' => 'success',
            'metadata' => [
                'created_user_id' => $user->id,
                'role' => $role->name,
                'verification_code' => $verification_code
            ]
            ]);

            return response()->json([
                'message' => 'User registered successfully. Check your email for the activation code.',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ], 201);
        } else {
            Log::create([
            'user_id' => auth()->id() ?? null,
            'action' => 'register_user',
            'details' => "New user registered: 
                Name - {$user->name}, 
                Email - {$user->email}, 
                Role - {$role->name}, 
                Verification Code - {$verification_code}",
            'type' => 'failed',
            'metadata' => [
                'created_user_id' => $user->id,
                'role' => $role->name,
                'verification_code' => $verification_code
            ]
            ]);

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
        // Log failed attempt
        Log::create([
            'user_id' => auth()->id() ?? null,
            'action' => 'activate_user',
            'details' => "Failed activation attempt for email: {$request->email} with code: {$request->verification_code}",
            'type' => 'error',
            'metadata' => [
                'email' => $request->email,
                'attempted_code' => $request->verification_code
            ]
        ]);

        return response()->json(['message' => 'Invalid code or email'], 400);
    }

    $user->is_verified = true;
    $user->verification_code = null;
    $user->save();

    // Log successful activation
    Log::create([
        'user_id' => $user->id,
        'action' => 'activate_user',
        'details' => "User {$user->name} ({$user->email}) activated their account successfully.",
        'type' => 'success',
        'metadata' => [
            'user_id' => $user->id,
            'email' => $user->email
        ]
    ]);

    return response()->json(['message' => 'Account activated successfully!']);
}
// Login endpoint
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        // Log failed login
        Log::create([
            'user_id' => $user->id ?? null,
            'action' => 'login',
            'details' => "Failed login attempt for email: {$request->email}",
            'type' => 'error',
            'metadata' => ['email' => $request->email]
        ]);

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    if (!$user->is_verified) {
        return response()->json(['message' => 'Account not activated'], 403);
    }

    // Generate API token (Sanctum)
    $token = $user->createToken('auth_token')->plainTextToken;

    // Log successful login
    Log::create([
        'user_id' => $user->id,
        'action' => 'login',
        'details' => "User {$user->name} ({$user->email}) logged in successfully.",
        'type' => 'success',
        'metadata' => ['user_id' => $user->id]
    ]);

    return response()->json([
        'message' => 'Login successful',
        'access_token' => $token,
        'token_type' => 'Bearer'
    ]);
}
public function logout(Request $request)
{
    $user = $request->user(); // authenticated via Sanctum

    if (!$user) {
        return response()->json(['message' => 'No active session found'], 400);
    }

    // Delete current token
    $token = $user->currentAccessToken();
    $tokenId = $token->id ?? null;
    $token->delete();

    // Log to MongoDB
    Log::create([
        'user_id' => $user->id,
        'action' => 'logout',
        'details' => "User {$user->name} ({$user->email}) logged out successfully.",
        'type' => 'success',
        'metadata' => [
            'token_id' => $tokenId,
            'email' => $user->email
        ]
    ]);

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
}

}
