<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Restaurent;
use App\Models\RestaurentImages;
use Validator;
use Hash;
use Log;
use App\Http\Traits\ResponseTrait;
use App\Http\Requests\Restaurent\CreateRestaurent;

class AdminController extends Controller
{
    use ResponseTrait;

    // Create Admin API 
    public function createRestaurent(CreateRestaurent $request)
    {
        $input = $request->all();
        $restaurent = Restaurent::create($input);
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->phone = $request->phone;
        $user->restaurent_id = $restaurent->id;
        $user->user_type = "restaurent";
        $user->save();
        if(isset($request->images) && !empty($request->images)){
            foreach($request->images as $image){
                $restaurentImage = new RestaurentImages;
                $restaurentImage->restaurent_id = $restaurent->id;
                $restaurentImage->image = $image;
                $restaurentImage->save();
            }
        }
        if(!empty($restaurent)){
            return $this->sendResponse($restaurent, 'Restaurent registered successfully.');
        }else{
            return $this->sendError('Restaurent was not registered due to an error, Try again.');
        }
    }
}
