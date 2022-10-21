<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Restaurent;
use App\Models\RestaurentImages;
use App\Models\Transaction;
use App\Models\Gifts;
use App\Models\NotificationLog;
use App\Models\QrCodes;
use Validator;
use Hash;
use Log;
use App\Http\Traits\ResponseTrait;
use App\Http\Requests\Transaction\CreateTransactionRequest;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionController extends Controller
{
    use ResponseTrait;

    // Create Payment Intent API 
    public function paymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError(implode(",", $validator->errors()->all()), []);
        }
        $amount = round($request->amount, 2);
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        $customer = $stripe->customers->create([
            'description' => 'Gift Customer',
        ]);

        $ephemeralKey = \Stripe\EphemeralKey::create(
            ['customer' => $customer->id],
            ['stripe_version' => '2020-08-27']
        );

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amount * 100,
            'currency' => 'usd',
            'customer' => $customer->id,
        ]);

        $pay_int_res = [
            'result' => 'Success',
            'message' => 'Payment intent successfully!',
            'payment_intent' => $paymentIntent->client_secret,
            'ephemeral_key' => $ephemeralKey->secret,
            'customer_id' => $customer->id,
            'publishablekey' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'intent_id' => $paymentIntent->id
        ];
        return $this->sendResponse($pay_int_res, 'Payment Intent generated successfully!');
    }

    // Create Transaction API 
    public function createTransaction(CreateTransactionRequest $request)
    {

        $loginUserId = Auth::user()->id;
        $transaction = new Transaction;
        $transaction->user_id = $loginUserId;
        $transaction->amount = $request->amount;
        $transaction->receiver_number = $request->receiver_number;
        $transaction->intent_id = $request->intent_id;
        $transaction->status = 1;
        $transaction->save();
        if ($transaction) {
            $gift = new Gifts;
            $gift->sender_id = $loginUserId;
            $gift->receiver_number = $request->receiver_number;
            $gift->restaurent_id = $request->restaurent_id;
            $gift->gift_amount = $request->amount;
            $gift->receiver_name = $request->receiver_name;
            $gift->is_used = 0;
            $gift->remaining_amount = $request->amount;
            $gift->save();

            // Save Data for QR code 
            $qrCode = new QrCodes;
            $qrCode->remaining_amount = $request->amount;
            $qrCode->restaurent_id = $request->restaurent_id;
            $qrCode->gift_id = $gift->id;
            $qrCode->transaction_id = $transaction->id;
            $qrCode->save();
            // Generate QR Code 
            $destinationPath = base_path() . '/public/qr_codes/';
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 777, true);
            }
            $name = time() . rand();
            $imagePath = $destinationPath . $name . '.svg';
            $uploadPath =  str_replace('/var/www/html/', "http://172.104.193.73/", $imagePath);
            $qrStringData = "token:" . $qrCode->id . ";date:" . $qrCode->created_at . ";restaurent_id:" . $qrCode->restaurent_id . ";";
            $code = QrCode::format('svg')->generate($qrStringData, $imagePath);
            QrCodes::where('id', $qrCode->id)->update(['qr_image' => $uploadPath]);
            // Check If user is registered on app or not 
            $user = User::where('phone', $request->receiver_number)->first();
            if (!empty($user)) {
                // Send Notification To gift Receiver 
                $notification = new NotificationLog;
                $notification->user_id = $loginUserId;
                $notification->notification_type = 'gift';
                $notification->amount = $request->amount;
                $notification->restaurent_id = $request->restaurent_id;
                $notification->receiver_id = $user->id;
                $notification->receiver_number = $request->receiver_number;
                $notification->gift_id = $gift->id;
                $notification->save();
            } else {
                // Send SMS if User not registered 

            }
            $success['transaction'] = $transaction;
            $success['qrCode'] = $qrCode;
            $success['qrCode']['qrImage'] = $uploadPath;
            return $this->sendResponse($success, 'Transaction Created Successfully');
        } else {
            return $this->sendError('Transaction was not created successfully');
        }
    }

    // Transaction History API 
    public function TransactionHistory()
    {
        $loginUserId = Auth::user()->id;
        $user = User::find($loginUserId);
        $restaurent = Restaurent::find($user->restaurent_id);
        if (empty($restaurent)) {
            return $this->sendError("Your restaurent profile does not exist. Login with restaurent profile.");
        }
        // $restaurent->restaurentImages = RestaurentImages::where('restaurent_id', $restaurent->id)->get();
        // $success['restaurent_detail'] = $restaurent;
        $gifts = Gifts::where(['restaurent_id' => $restaurent->id, 'is_used' => 1])->get();
        if (count($gifts) > 0) {
            foreach ($gifts as $gift) {
                $usedAmount = $gift['gift_amount'] - $gift['remaining_amount'];
                $userImage = User::where('phone', $gift['receiver_number'])->pluck('profile_img')->first();
                $gift['usedAmount'] = $usedAmount;
                $gift['userImage'] = $userImage;
                $qrImage = QrCodes::where('gift_id', $gift->id)->pluck('qr_image')->first();
                $gift['qr_image'] = $qrImage;
            }
            $success['usedGifts'] = $gifts;
        } else {
            $success['usedGifts'] = [];
        }
        return $this->sendResponse($success, "Transaction History Data");
    }
}
