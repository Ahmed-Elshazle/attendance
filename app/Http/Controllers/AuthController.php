<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Otp;
use App\Models\User;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
    
        if (auth()->attempt($fields)) {
            $user = auth()->user();
            $relations = match ($user->role) {
                'student' => ['student:id,department,grade,date_of_birth,address'],
                'doctor' => ['doctor:id,department'],
                'assistant' => ['assistant:id,department'],
                default => [],
            };

            if (!empty($relations)) {
                $user->load($relations);
            }

            $abilities = match ($user->role) {
                'admin' => ['admin', 'student_affairs'],
                'student_affairs' => ['student_affairs', 'admin'],
                'doctor' => ['doctor'],
                'assistant' => ['doctor'],
                'student' => ['student'],
                default => [],
            };
    
            $token = $user->createToken('auth_token', $abilities)->plainTextToken;
    
            return response()->json([
                'user' => $user,
                'token' => $token
            ], 201);
        }
    
        return response()->json([
            'message' => 'invalid credentials'
        ], 401);
    }

    public function logout(Request $request){
        auth()->user()->tokens()->delete();
        return response()->json(['message' => 'logged out']);
    }
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $otpCode = $this->generateOtpCode();
        $expiresAt = Carbon::now()->addMinutes(5);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'email not found'], 404);
        }

        try {
            Otp::updateOrCreate(
                ['email' => $request->email],
                ['otp_code' => $otpCode, 'expires_at' => $expiresAt]
            );

            $userName = $user->name;
            Mail::to($request->email)->send(new SendOtpMail($otpCode, $userName));

            return response()->json(['message' => 'the OTP has been sent successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'failed to send OTP'], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:4',
        ]);

        $otp = Otp::where('email', $request->email)
        ->where('otp_code', $request->otp)
        ->where('expires_at', '>', Carbon::now())
        ->first();

        if (!$otp) {
            return response()->json(['message' => 'OTP expired or invalid'], 422);
        }

        return response()->json(['message' => 'OTP is valid'], 200);
    }

    public function verifyOtpAndResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:4',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $otp = Otp::where('email', $request->email)
        ->where('otp_code', $request->otp)
        ->where('expires_at', '>', Carbon::now())
        ->first();

        if (!$otp) {
            return response()->json(['message' => 'OTP expired or invalid'], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->new_password);
        $user->save();

        $otp->delete();

        return response()->json(['message' => 'password updated successfully']);
    }

    private function generateOtpCode($length = 4) {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    public function add_users(){
        
        // User::create([
        //     'name' => 'ahmed',
        //     'email' => 'ahmoidysolid@gmail.com',
        //     'phone' => '01000000000',
        //     "role" => "student",
        //     'password' => Hash::make('123456'), 
        // ]);

        // User::create([
        //     'name' => 'doc',
        //     'email' => 'doc@gmail.com',
        //     'phone' => '01000000000',
        //     "role" => "doctor",
        //     'password' => Hash::make('123456'), 
        // ]);

        // User::create([
        //     'name' => 'assistant',
        //     'email' => 'assistant@gmail.com',
        //     'phone' => '01000000000',
        //     "role" => "assistant",
        //     'password' => Hash::make('123456'), 
        // ]);

        // User::create([
        //     'name' => 'aff',
        //     'email' => 'aff@gmail.com',
        //     'phone' => '01000000000',
        //     "role" => "student_affairs",
        //     'password' => Hash::make('123456'), 
        // ]);

        return response()->json(['message' => 'bruh']);
    }

}
