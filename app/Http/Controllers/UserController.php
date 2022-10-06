<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Restaurent;
use App\Models\Contacts;
use App\Models\Gifts;
use App\Models\RestaurentImages;
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
            if ($request->hasFile('profile_image')) {
            $destinationPath = base_path() . '/public/user_images/';
            $uploadPath =  str_replace("/var/www/html", "", $destinationPath);
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 777, true);
            }
            $image = $request->file('profile_image');

            $name = time() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $name);
            $profile_image = $uploadPath . $name;
            dd($profile_image);
            User::where('id', $loginUserId)->update(['profile_img' => $profile_image]);
        }
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

    // Restaurent Dashboard API 
    public function restaurentDashboard()
    {
        $loginUserId = Auth::user()->id;
        $user = User::find($loginUserId);
        $restaurent = Restaurent::find($user->restaurent_id);
        $restaurent->restaurentImages = RestaurentImages::where('restaurent_id', $restaurent->id)->get();
        $success['restaurent_detail'] = $restaurent;
        $gifts = Gifts::where(['restaurent_id' => $restaurent->id, 'is_used' => 1])->get();
        if(count($gifts) > 0){
            foreach($gifts as $gift){
                $usedAmount = $gift['gift_amount'] - $gift['remaining_amount'];
                $userImage = User::where('phone', $gift['receiver_number'])->pluck('profile_img')->first();
                $gift['usedAmount'] = $usedAmount;
                $gift['userImage'] = $userImage;
            }
            $success['usedGifts'] = $gifts;
        }else{
            $success['usedGifts'] = [];
        }
        return $this->sendResponse($success,"Restaurent Dashboard data");
    }
}
