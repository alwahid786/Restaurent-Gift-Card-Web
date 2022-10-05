<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Restaurent;
use App\Models\RestaurentImages;
use App\Models\Gifts;
use App\Models\QrCodes;
use Validator;
use Hash;
use Log;
use App\Http\Traits\ResponseTrait;
use App\Http\Requests\Restaurent\GiftIdRequest;
use App\Http\Requests\Restaurent\DebitVoucherRequest;
use App\Models\NotificationLog;

class NotificationController extends Controller
{
    use ResponseTrait;

    // Show list of all notifications API 
    public function notificationList()
    {
        $loginUserId = Auth::user()->id;
        $notifications = NotificationLog::where('user_id', $loginUserId)->orwhere('receiver_id', $loginUserId)->get();
        if(count($notifications) > 0){
            foreach($notifications as $notification){
                // Check If notification type is gift 
                if($notification->notification_type == 'gift'){
                    if($notification->receiver_id == $loginUserId){
                        $user = User::find($notification->user_id);
                        $restaurent = Restaurent::find($notification->restaurent_id);
                        $message = $user->name . "sent you a gift voucher of ". $notification->amount . " at ". $restaurent->name;
                        $notification['message'] = $message;
                    }else{
                        continue;
                    }
                }
                // Check If notification type is debit_gift 
                elseif($notification->notification_type == 'debit_gift'){
                    if($notification->receiver_id == $loginUserId){
                        $user = User::find($notification->user_id);
                        $restaurent = Restaurent::find($user->restaurent_id);
                        $message = "You have redeemed a gift voucher of ". $notification->amount . " at ". $restaurent->name;
                        $notification['message'] = $message;

                    }else{
                        continue;
                    }
                }
                // Check If notification type is gift_added 
                elseif($notification->notification_type == 'gift_added'){
                    if($notification->user_id == $loginUserId){
                        $user = User::find($notification->receiver_id);
                        $message = $user->name." have redeemed a gift voucher of ". $notification->amount . " at your restaurent";
                        $notification['message'] = $message;
                    }else{
                        continue;
                    }
                }
            }
            return $this->sendResponse($notifications, "List of all notifications");
        }else{
            return $this->sendError('No notifications for this user');
        }
    }
}
