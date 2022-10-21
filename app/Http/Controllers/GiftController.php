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


class GiftController extends Controller
{
    use ResponseTrait;

    // List of All restaurents API 
    public function restaurentList(Request $request)
    {
        $restaurents = Restaurent::get();
        if (count($restaurents) > 0) {
            foreach ($restaurents as $restaurent) {
                $restaurent['menu'] = RestaurentImages::where('restaurent_id', $restaurent['id'])->get();
            }
            return $this->sendResponse($restaurents, 'List of all Restaurents');
        } else {
            return $this->sendError('No Restaurent available');
        }
    }

    // List of images in menu API
    public function menuImages(Request $request)
    {
        $images = RestaurentImages::where('restaurent_id', $request->restaurent_id)->get();
        if (count($images) > 0) {
            return $this->sendResponse($images, 'Menu for this restaurent');
        } else {
            return $this->sendError('No Images for this restaurent available');
        }
    }

    // List of sent gifts API 
    public function sentGifts()
    {
        $loginUserId = Auth::user()->id;
        $gifts = Gifts::where('sender_id', $loginUserId)->get();
        if (count($gifts) > 0) {
            foreach ($gifts as $gift) {
                $user = User::where('phone', $gift->receiver_number)->first();
                if (!empty($user)) {
                    $gift['receiver_image'] = $user->profile_img;
                }
            }
            return $this->sendResponse($gifts, "List of all sent gifts");
        } else {
            return $this->sendError('No gifts sent yet!');
        }
    }

    // List of received gifts API 
    public function receivedGifts()
    {
        $loginUserId = Auth::user()->id;
        $number = User::where('id', $loginUserId)->pluck('phone')->first();
        $gifts = Gifts::where('receiver_number', $number)->get();
        $balance = 0;
        if (count($gifts) > 0) {
            foreach ($gifts as $gift) {
                $balance = $balance + $gift->remaining_amount;
                $user = User::where('id', $gift->sender_id)->first();
                if (!empty($user)) {
                    $gift['sender_image'] = $user->profile_img;
                }
                $qrImage = QrCodes::where('gift_id', $gift->id)->pluck('qr_image')->first();
                $gift['qr_image'] = $qrImage;
            }
            // $gifts['availableBalance'] = $balance;
            $success['availableBalance'] = $balance;
            $success['receivedGifts'] = $gifts;
            return $this->sendResponse($success, "List of all sent gifts");
        } else {
            return $this->sendError('No gifts received yet!');
        }
    }

    // Show Gift Amount API 
    public function giftAmount(GiftIdRequest $request)
    {
        $giftdetail = QrCodes::find($request->token_id);
        if (!empty($giftdetail)) {
            return $this->sendResponse($giftdetail, "Gift Details Found against token id");
        } else {
            return $this->sendError('No gift found against this token.');
        }
    }

    // Cut amount from voucher API 
    public function debitVoucher(DebitVoucherRequest $request)
    {
        $loginUserId = Auth::user()->id;
        $remainAmount = QrCodes::where('id', $request->token_id)->pluck('remaining_amount')->first();
        if ($request->bill_amount >= $remainAmount) {
            $usedAmount = $remainAmount;
            QrCodes::where('id', $request->token_id)->update(['remaining_amount' => 0]);
            Gifts::where('id', $request->gift_id)->update(['remaining_amount' => 0, "is_used" => 1]);
        } else {
            $remainAmount = $remainAmount - $request->bill_amount;
            $usedAmount = $request->bill_amount;
            QrCodes::where('id', $request->token_id)->update(['remaining_amount' => $remainAmount]);
            Gifts::where('id', $request->gift_id)->update(['remaining_amount' => $remainAmount, "is_used" => 1]);
        }
        if ($remainAmount == 0) {
            return $this->sendError('No money remaining in voucher');
        }

        // Add amount to restaurent balance
        $restaurent = User::find($loginUserId);
        $total_balance = Restaurent::select('total_balance', 'released_balance')->where('id', $restaurent->restaurent_id)->first()->toArray();
        $newBalance = $total_balance['total_balance'] + $usedAmount;
        $pending_balance = $total_balance['total_balance'] - $total_balance['released_balance'];
        $saveData = Restaurent::where('id', $restaurent->restaurent_id)->update(['total_balance' => $newBalance, 'pending_balance' => $pending_balance]);

        $receiverNumber = Gifts::where('id', $request->gift_id)->pluck('receiver_number')->first();
        $user = User::where('phone', $receiverNumber)->first();

        // Send Notification to User 
        $userNotification = new NotificationLog;
        $userNotification->user_id = $loginUserId;
        $userNotification->notification_type = 'debit_gift';
        $userNotification->amount = $usedAmount;
        $userNotification->gift_id = $request->gift_id;
        if (!empty($user)) {
            $userNotification->receiver_id = $user->id;
        }
        $userNotification->receiver_number     = $receiverNumber;
        $userNotification->save();

        // Send Notification to restaurent 
        $restaurentNotification = new NotificationLog;
        $loginUserId = Auth::user()->id;
        $restaurentNotification->user_id = $loginUserId;
        $restaurentNotification->notification_type = 'gift_added';
        $restaurentNotification->amount = $usedAmount;
        $restaurentNotification->gift_id = $request->gift_id;
        $restaurentNotification->receiver_id = $user->id;
        $restaurentNotification->receiver_number = $receiverNumber;
        $restaurentNotification->save();

        return $this->sendResponse([], 'The gift card has been debited successfully!');
    }
}
