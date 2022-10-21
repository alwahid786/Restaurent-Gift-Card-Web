<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;
use Hash;
use Log;
use App\Http\Traits\ResponseTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTPMail;
use Illuminate\Support\Facades\Password;
use App\Models\Restaurent;



class AuthController extends Controller
{
    use ResponseTrait;

    // Register API 
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError(implode(",", $validator->messages()->all()));
        }
        
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $userData = User::find($user->id);
        $success['user'] =  $userData;
        $success['user']['token'] =  $user->createToken('authToken')->accessToken;
        return $this->sendResponse($success, 'User registered successfully.');
    }

    // Login API 
    public function login(Request $request)
    {
        Validator::extend('without_spaces', function ($attr, $value) {
            return preg_match('/^\S*$/u', $value);
        });
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|without_spaces|exists:users',
                'password' => 'required',
            ],
            [
                'without_spaces' => 'White Spaces Not Allowed in Email'
            ]
        );
        if ($validator->fails()) {
            return $this->sendError(implode(",", $validator->messages()->all()));
        }
        // Attempt Login after validation 
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $userId = Auth::user()->id;
            $user = User::find($userId);
            if($user->user_type == 'restaurent'){
                $user['restaurentData'] = Restaurent::where('id', $user->restaurent_id)->first();
            }
            $user['token'] = $user->createToken('loginToken')->accessToken;
            return $this->sendResponse($user, 'User logged in successfully.');
        }else{
            return $this->sendError('Password is incorrect!');
        }
    }

    // Send OTP Code API 
    public function sendOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email'
        ]);
        if ($validator->fails()) {
            return $this->sendError(implode(",", $validator->messages()->all()));
        }
        $otp = rand(1000,9999);
        Log::info("otp = ".$otp);
        $user = User::where('email','=',$request->email)->update(['otp' => $otp]);
        if($user){
        // \Mail::to($request->email)->send(new SendOTPMail($otp));
            return $this->sendResponse($otp, 'OTP sent successfully.');
        }
        else{
            return $this->sendError('Something went wrong, User was not found. Try again later.');
        }
    }

    // Verify OTP API 
    public function verifyOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
            'otp' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError(implode(",", $validator->messages()->all()));
        }
        $user  = User::where([['email','=',$request->email],['otp','=',$request->otp]])->first();
        if($user){
            auth()->login($user, true);
            User::where('email','=',$request->email)->update(['otp' => null]);
            $success['user'] = auth()->user();
            $success['user']['token'] = auth()->user()->createToken('authToken')->accessToken;
            
            // return response(["status" => 200, "message" => "Success", 'user' => auth()->user(), 'access_token' => $accessToken]);
            return $this->sendResponse($success, 'OTP Code Verified Successfully!');
        }
        else{
            return $this->sendError('Invalid OTP Code!');
        }
    }

    // Reset Password API 
    public function resetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError(implode(",", $validator->messages()->all()));
        }
        $password = Hash::make($request->password);
        $id = Auth::user()->id;
        $user  = User::where('id', $id)->update(['password' => $password]);
        if($user){
            return $this->sendResponse([], 'Your password has been Successfully updated.');
        }else{
            return $this->sendError("something went wrong, Try again.");
        }
    }

    // Change Password API 
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_pass' => 'required',
            'new_pass' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError(implode(",", $validator->messages()->all()));
        }
        $loginUserId = auth()->user()->id;
        $user = User::find($loginUserId);
            if (Hash::check($request->old_pass, $user->password)) {

            $passoword = Hash::make($request->new_pass);
            User::where('id', $loginUserId)->update(['password' => $passoword]);
            return $this->sendResponse([], 'Your password has been Successfully updated.');
        } else {
            $response = "Old Password is incorrect";
            return $this->sendError(($response), []);
        }
    }

    // Logout API 
    public function logout(Request $request)
    {
        $id = Auth::user()->token();
        $id->revoke();
        $response = 'You have been successfully logged out!';
        return $this->sendResponse([], $response);
    }
    
}
