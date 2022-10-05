<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Contacts;
use Validator;
use Hash;
use Log;
use App\Http\Traits\ResponseTrait;
use App\Http\Requests\User\CreateContactRequest;

class UserController extends Controller
{
    use ResponseTrait;

    // Edit Profile API 
    public function editProfile(Request $request)
    {
        $loginUserId = Auth::user()->id;
        if(isset($request->profile_image) && !empty($request->profile_image)){
            User::where('id', $loginUserId)->update(['profile_img' => $request->profile_image]);
        }
        if(isset($request->name) && !empty($request->name)){
            User::where('id', $loginUserId)->update(['name' => $request->name]);
        }
        if(isset($request->phone) && !empty($request->phone)){
            User::where('id', $loginUserId)->update(['phone' => $request->phone]);
        }
        if(isset($request->email) && !empty($request->email)){
            User::where('id', $loginUserId)->update(['email' => $request->email]);
        }
        
        if(empty($request->all())){
            return $this->sendError('You must provide at least one parameter to update your profile.');
        }
        $user = User::find($loginUserId);
        return $this->sendResponse($user, 'Profile updated successfully!');
    }

    // Create Contact API 
    public function createContact(CreateContactRequest $request)
    {
        $input = $request->all();
        $contact = Contacts::create($input);
        if(!empty($contact)){
            return $this->sendResponse($contact, 'Contact added successfully.');
        }else{
            return $this->sendError('Something went wrong, Try again.');
        }
    }

    // List of Contacts API 
    public function contactList()
    {
        $loginUserId = Auth::user()->id;
        $contacts = Contacts::where('user_id', $loginUserId)->get();
        if(count($contacts) > 0){
            return $this->sendResponse($contacts, 'List of All contacts against this user.');
        }else{
            return $this->sendError('No contacts found against this user.');
        }
    }
}
