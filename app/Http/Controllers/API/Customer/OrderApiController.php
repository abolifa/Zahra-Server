<?php

namespace App\Http\Controllers\API\Customer;

use App\Helpers\CommonHelper;
use App\Helpers\ProductHelper;
use App\Helpers\Paypal;
use App\Helpers\PaypalClient;
use App\Helpers\Paystack;
use App\Helpers\Paytm;
use App\Helpers\TLYNC;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AppUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderStatusList;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\PromoCode;
use App\Notifications\OrderNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Response;

class OrderApiController extends Controller
{
    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total' => 'required',
            'delivery_charge' => 'required',
            'delivery_time' => 'required',
            'final_total' => 'required',
            'payment_method' => 'required',
            'address_id' => 'required',
            'quantity' => 'required'
        ], [
            'required' => 'The :attribute field is required.',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }
        $user = auth()->user();
        if (!isset($user->status) || $user->status == 0) {
            return CommonHelper::responseError(__('not_allowed_to_place_order_as_your_account_is_de_activated'));
        }

        /* getting user address data */
        $address_id = $request->address_id;
        $user_address = CommonHelper::getUserAddress($request->address_id);
        if (!empty($user_address)) {
            $address = $user_address->address . ' ' . $user_address->landmark . ' ' . $user_address->area . ' ' . $user_address->city . ' ' . $user_address->state . ' ' . $user_address->country . '-' . $user_address->pincode . ' ' . $user_address->name . ' ' . $user_address->mobile . '/' . $user_address->alternate_mobile;
            $mobile = $user_address->mobile;
            $latitude = $user_address->latitude;
            $longitude = $user_address->longitude;
            $pincode_id = $user_address->pincode_id;
            $area_id = $user_address->area_id ?? 0;
        } else {
            return CommonHelper::responseError(__('something_is_missing_in_your_address'));
        }

        $user_id = auth()->user()->id;
        $order_note = (isset($request->order_note) && !empty($request->order_note)) ? $request->order_note : "";
        $wallet_used = (isset($request->wallet_used) && !empty($request->wallet_used) == 'true') ? 'true' : 'false';
        $items = $request->product_variant_id;

        $total = floatval($request->total);
        $delivery_charge = floatval($request->delivery_charge);
        $final_total = floatval($request->final_total);

        $promo_code = "";
        $promo_discount = 0;
        $promo_code_id = 0;

        if (isset($request->promocode_id) && $request->promocode_id && $request->promocode_id != "") {

            $code = PromoCode::find($request->promocode_id);

            if (empty($code)) {
                return CommonHelper::responseError("Promo code not found!");
            }
            $promo = CommonHelper::validatePromoCode($user_id, $code->promo_code, $total);

            if ($promo['is_applicable'] == 0) {
                return CommonHelper::responseError($promo['message']);
            }

            if (isset($promo['promo_code_id']) && $request->promocode_id == $promo['promo_code_id']) {
                $final_total = $promo['discounted_amount'] + $delivery_charge;
                $promo_discount = $promo['discount'];
                $promo_code = $promo['promo_code'] . "(" . $promo['discount'] . ")";
                $promo_code_id = $promo['promo_code_id'];
            }
        }

        $wallet_balance = (isset($request->wallet_balance) && is_numeric($request->wallet_balance)) ? $request->wallet_balance : 0;
        $payment_method = $request->payment_method;
        $delivery_time = (isset($request->delivery_time)) ? $request->delivery_time : "";

        $active_status = $payment_method == Transaction::$paymentTypeCod ? OrderStatusList::$received : OrderStatusList::$paymentPending;
        $order_from = (isset($request->order_from) && !empty($request->order_from)) ? $request->order_from : 0;

        $status[] = array($active_status, date("d-m-Y h:i:sa"));
        $quantity = $request->quantity;

        $quantity_arr = explode(",", $quantity);
        $item_arr = explode(",", $items);


        foreach ($item_arr as $key => $item) {
            $variant = ProductVariant::where("id", $item)->first();
            if (empty($variant)) {
                return CommonHelper::responseError(__('found_one_or_more_items_in_order_is_not_available_for_order'));
            }
        }

        $item_details = CommonHelper::getProductByVariantId($item_arr);


        $totalTax = CommonHelper::calculateOrderTotalTax($item_details, $quantity_arr);
        $order_total_tax_amt = $totalTax['order_total_tax_amt'];
        $order_total_tax_per = $totalTax['order_total_tax_per'];

        /*$order_total_tax_amt = 0;
        $order_total_tax_per = 0;
        foreach ($item_details as $key => $item) {
            $price = $item->price;
            $discounted_price = (empty($item->discounted_price) || $item->discounted_price == "") ? 0 : $item->discounted_price;
            $quantity = $quantity_arr[$key];
            $tax_percentage = (empty($item->tax_percentage) || ($item->tax_percentage == "")) ? 0 : $item->tax_percentage;
            $final_price = ($discounted_price != 0) ? ($discounted_price * $quantity) : ($price * $quantity);
            $tax_count = ($tax_percentage / 100) * $final_price;
            $order_total_tax_amt += $tax_count;
            $order_total_tax_per += $tax_percentage;
        }*/




        $generate_otp = Setting::get_value("generate_otp");
        if ($generate_otp == 1) {
            $otp_number = mt_rand(100000, 999999);
        } else {
            $otp_number = 0;
        }

        /* check for wallet balance */
        if ($wallet_used == 'true') {
            $user_wallet_balance = auth()->user()->balance;
            if ($user_wallet_balance < $wallet_balance) {
                return CommonHelper::responseError(__('insufficient_wallet_balance'));
            }
        }

        /* check for minimum order amount */
        $min_order_amount = Setting::get_value("min_order_amount");
        if ($final_total < $min_order_amount) {
            return CommonHelper::responseError("Minimum order amount is " . $min_order_amount . ".");
        }

        $walletvalue = ($wallet_used) ? $wallet_balance : 0;
        $order_status = json_encode($status);


        /* check whether points are in delivarable area or not */
        $seller_ids = array_values(array_unique(array_column($item_details->toArray(), "seller_id")));
        if (!CommonHelper::isDeliverableOrder($address_id, $latitude, $longitude, $seller_ids[0]) && !isDemoMode()) {
            return CommonHelper::responseError(__('sorry_we_are_not_delivering_on_selected_address'));
        }


        /* insert data into order table */
        $orders_id = CommonHelper::generateOrderId();

        DB::beginTransaction();
        try {

            $order = new Order();
            $order->user_id = $user_id;
            $order->delivery_boy_id = 0;
            $order->transaction_id = 0;
            $order->orders_id = $orders_id;
            $order->otp = $otp_number;
            $order->mobile = $mobile;
            $order->order_note = $order_note;
            $order->total = $total;
            $order->delivery_charge = $delivery_charge;
            $order->tax_amount = $order_total_tax_amt;
            $order->tax_percentage = $order_total_tax_per;
            $order->wallet_balance = $walletvalue;
            $order->promo_code_id = $promo_code_id;
            $order->promo_code = $promo_code;
            $order->promo_discount = $promo_discount;

            $order->final_total = $final_total;
            $order->payment_method = $payment_method;
            $order->address = $address;
            $order->latitude = $latitude;
            $order->longitude = $longitude;
            $order->delivery_time = $delivery_time;
            $order->status = $order_status;
            $order->active_status = $active_status;
            $order->order_from = $order_from;
            $order->pincode_id = $pincode_id;
            $order->area_id = $area_id;
            $order->address_id = $address_id;
            $order->save();

            $order_id = $order->id;
            if ($order_id == "") {
                return CommonHelper::responseError(__('order_can_not_place_due_to_some_reason_try_again_after_some_time'));
            }

            /* process wallet balance */
            $user_wallet_balance = $user->balance;
            if ($wallet_used == 'true') {
                /* deduct the balance & set the wallet transaction */
                $new_balance = $user_wallet_balance < $wallet_balance ? 0 : $user_wallet_balance - $wallet_balance;
                CommonHelper::updateUserWalletBalance($new_balance, $user_id);
                CommonHelper::addWalletTransaction($order_id, 0, $user_id, 'debit', $wallet_balance, 'Used against Order Placement');
            }

            /* process each product in order from variants of products */
            foreach ($item_details as $key => $item) {
                $product_id = $item->product_id;
                $product_name = $item->name;
                $measurement = $item->measurement;
                $variant_name = $measurement . $item->stock_unit_name;
                $product_variant_id = $item->id;
                $stock_unit_id = $item->stock_unit_id;
                $price = $item->price;
                $discounted_price = (empty($item->discounted_price) || $item->discounted_price == "") ? 0 : $item->discounted_price;
                $is_unlimited_stock = $item->is_unlimited_stock;
                $type = $item->product_type;

                $total_stock = $item->stock;
                $quantity = $quantity_arr[$key];
                $tax_title = $item->tax_title;
                $seller_id = (!empty($item->seller_id)) ? $item->seller_id : "";
                $tax_percentage = (empty($item->tax_percentage) || $item->tax_percentage == "") ? 0 : $item->tax_percentage;
                $tax_amt = $discounted_price != 0 ? (($tax_percentage / 100) * $discounted_price) : (($tax_percentage / 100) * $price);
                $sub_total = $discounted_price != 0 ? ($discounted_price + ($tax_percentage / 100) * $discounted_price) * $quantity : ($price + ($tax_percentage / 100) * $price) * $quantity;

                $neworder_id = $order_id;
                $tax_amount = $tax_amt;
                $order_sub_total = $sub_total;
                $order_item_status = json_encode($status);

                $order_item = new OrderItem();
                $order_item->user_id = $user_id;
                $order_item->order_id = $neworder_id;

                $order_item->orders_id = $orders_id;

                $order_item->product_name = $product_name;
                $order_item->variant_name = $variant_name;
                $order_item->product_variant_id = $product_variant_id;
                $order_item->quantity = $quantity;

                $order_item->price = $price;
                $order_item->discounted_price = $discounted_price;

                $order_item->tax_amount = $tax_amount;
                $order_item->tax_percentage = $tax_percentage;
                $order_item->sub_total = $order_sub_total;
                $order_item->status = $order_item_status;
                $order_item->active_status = $active_status;
                $order_item->seller_id = $seller_id;
                $order_item->save();

                if ($is_unlimited_stock != 1) {
                        $stock = $quantity * $measurement;
                        $product_variant = ProductVariant::where("id", $product_variant_id)->first();
                        $product_variant->stock = $stock;
                        $product_variant->save();

                        if ($product_variant->stock <= 0) {
                            $product_variant->status = 0;
                            $product_variant->save();
                        }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            return CommonHelper::responseError(__('could_not_place_order_try_again'));
        }


        try {
            CommonHelper::sendNotificationOrderStatus($order);
            $admins = Admin::get();
            foreach ($admins as $admin) {
                $admin->notify(new OrderNotification($order->id, 'new'));
            }
        } catch (\Exception $e) {
        }

        if ($payment_method == Transaction::$paymentTypeCod) {
            CommonHelper::addSellerWiseOrder($order->id);
            return CommonHelper::responseSuccess(__('order_placed_successfully'));
        } else {
            return CommonHelper::responseWithData(['order_id' => $order->id]);
        }
    }

    public function deletePaymentPendingOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $order = Order::find($request->order_id);
        if (empty($order)) {
            return CommonHelper::responseError("Order Not found!");
        }

        if ($order->active_status != OrderStatusList::$paymentPending) {
            $statusName = OrderStatusList::where('id', $order->active_status)->value('status');
            return CommonHelper::responseError("Now you order status is " . $statusName);
        }

        DB::beginTransaction();
        try {

            if ($order->delete()) {
                OrderItem::where('order_id', $request->order_id)->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Error : " . $e->getMessage());
            throw $e;
            return CommonHelper::responseError("Something Went Wrong!");
        }
        return CommonHelper::responseSuccess("Order deleted successfully");
    }

    public function orderTest(Request $request)
    {
        $result = CommonHelper::findGoogleMapDistanceLocal(23.24114205388701, 69.66720847135304, 23.235700208395272, 69.7287490771754);
        return CommonHelper::responseWithData($result);
    }

    public function initiateTransaction(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'payment_method' => 'required',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        // $order = Order::with('user')->where('id', $request->order_id)
        //     ->first();

        $order = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.id', $request->order_id)
            ->select('orders.*', 'users.*')
            ->first();



        // $u = Order::get();
        // dd($u);
        // dd($order);
        if (!$order) {
            return CommonHelper::responseError("Order not found!");
        }

        $out['payment_method'] = $request->payment_method;
        // $out['transaction_id'] = "";
        $transaction_id = "";

        if ($request->payment_method == "Razorpay") {

            \Log::error("payment_method = " . $request->payment_method);

            $transaction_id = TransactionHelper::createOrderonRazorpay($order->id);
            if ($transaction_id == "") {
                return CommonHelper::responseError("Error while communicating with razorpay server");
            }
        } else if ($request->payment_method == "Paypal") {

            $user_id = auth()->user()->id;
            $order_id = $request->order_id;

            $order = Order::where('id', $order_id)->first();

            if (!empty($order)) {
                $out['paypal_redirect_url'] = url('customer/paypal_payment_url?user_id=' . $user_id . '&order_id=' . $order_id);
            }
        } else if ($request->payment_method == "Stripe") {

            $user_id = auth()->user()->id;
            $order_id = $request->order_id;

            //  $order = Order::where('id', $order_id)->first();
            $user = User::find($user_id);
            $order = DB::table('orders')->where('id', $order_id)->first();
            //  dd("sdvfsdf");
            $existed_unique_ref = $order->ref_id;
            $random_code = bin2hex(random_bytes(6));
            if ($existed_unique_ref != $random_code) {
                $unique_ref = $random_code;
            } else {
                $unique_ref = bin2hex(random_bytes(7));
            }
            $website_url = config('app.website_url');
            $parameters = [
                'id'            => 'knJR75qVeObWJwQM1nRqorBgzaAkEmZx9z8Vx0NyXK963PL27dYD5Gjl4GmBXxLA',
                'amount'        => $order->final_total,
                'phone'         => $order->mobile,
                'email'         =>  $user->email,
                'backend_url'   => $website_url . 'payment/callback',
                'frontend_url'  => $website_url . 'payment/callback',
                'custom_ref'     => $unique_ref
            ];

            $parameters = json_encode($parameters);
            $ipn_site_url = 'https://c7drkx2ege.execute-api.eu-west-2.amazonaws.com/payment/initiate';

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $ipn_site_url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);

            $authorizationToken = 'HCOxqpJnHH2JX5ust8LOqvQoA9nanDcNHnx4uaVc';
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $authorizationToken",
                "Content-Type: application/json",
                "Accept: application/json"
            ]);

            $result = curl_exec($ch);

            $data = json_decode($result, true);
 //dd($data);
           if (isset($data['url'])) {
                $url = $data['url'];
            } else {
                $url = "";
            }

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);

                return "cURL Error: $error_msg";
            }

            curl_close($ch);

            $orderUpdated = DB::table('orders')
                ->where('id', $request->order_id)
                ->update([
                    'payment_method' => "TLYNC",
                    'ref_id' => $unique_ref,
                ]);

  return CommonHelper::responseWithData($data);

          //  return redirect()->away($url);
            //return $result;

            if (!empty($order)) {
                $out['tlync_redirect_url'] = url('customer/tlync_payment_url?user_id=' . $user_id . '&order_id=' . $order_id);
            }
        } else if ($request->payment_method == "Strinnnnpe") {

            \Log::error("payment_method = " . $request->payment_method);


            $response = TransactionHelper::createOrderOnStripe($order);
            \Log::error("order->final_total  = " . $order->final_total);

            if ($response == "") {
                return CommonHelper::responseError("Error while communicating with Stripe server");
            }
            $out = $response->toArray();

            $orderUpdated = DB::table('orders')
                ->where('id', $request->order_id)
                ->update([
                    'payment_method' => $request->payment_method,
                    'ref_id' => $unique_ref,
                ]);
            //  $order->payment_method = $request->payment_method;
            // $order->save();
        } else {
            return CommonHelper::responseError("Invalid payment methods.");
            //$transaction_id = round(microtime(true) * 1000);
        }

        DB::table('orders')
            ->where('id', $request->order_id)
            ->update(['payment_method' => $request->payment_method]);

        // $order->payment_method = $request->payment_method;
        // $order->save();

        if ($transaction_id != "") {
            $out['transaction_id'] = $transaction_id;
        }
        return CommonHelper::responseWithData($out);
    }



    // public function handleCallback(Request $request)
    // {
    //     $paymentData = $request->all();


    //     //Log::info('Payment Callback Data:', $paymentData);



    //     if ($paymentData['status'] == 'success') {

    //         return redirect()->route('payment.success');
    //     } else {

    //         return redirect()->route('payment.failed');
    //     }
    //     return CommonHelper::responseSuccess("Order deleted successfully");
    // }





    /*Paypal Start*/
    public function paypalPaymentUrl(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $app_name = Setting::get_value('app_name');

        $user = User::where('id', $request->user_id)->first();
        $order = Order::where('id', $request->order_id)->first();
        if ($order) {

            header("Content-Type: html");

            $amount = $order->final_total;

            $data['user'] = $user;
            $data['order'] = $order;
            $data['payment_type'] = "paypal";
            // Set variables for paypal form
            $returnURL = url('customer/paypal_redirect/success');
            $cancelURL = url('customer/paypal_redirect/fail');
            $notifyURL = url('customer/ipn');
            $txn_id = time() . "-" . rand();
            // Get current user ID from the session
            $userID = $data['user']['id'];
            $order_id = $data['order']['id'];
            $payeremail = $data['user']['email'];
            // $userID = $data['user']->id;

            $paypal = new Paypal();
            // Add fields to paypal form
            $paypal->add_field('return', $returnURL);
            $paypal->add_field('cancel_return', $cancelURL);
            $paypal->add_field('notify_url', $notifyURL);
            $paypal->add_field('item_name', $app_name);
            $paypal->add_field('custom', $userID . '|' . $payeremail);
            $paypal->add_field('item_number', $order_id);
            $paypal->add_field('amount', $amount);

            // Render paypal form
            $paypal->paypal_auto_form();
        }
    }

    public function handleFrontendCallback(Request $request){

        dd($request->getContent());

    }

    public function handleCallback(Request $request)
    {
      //  $paymentData = $request->all();
    //    $website_url = config('app.website_url');

// $order = DB::table('orders')->where('id', 1)->update([
//     'active_status' => 11,
//     'transaction_id' => 10,
// ]);


//                     $data['transaction_type'] = 'Transaction';
//                     $data['user_id'] =2;
//                     //$data['payer_email']  = $userData[1];
//                     $data['order_id'] =1;
//                     $data['type'] = 'paypal';
//                     $data['txn_id'] = 'sdfkshdfushf345';
//                     $data['payu_txn_id'] = "_";
//                     $data['amount'] = 30;
//                     //$data['currency_code'] = $paypalInfo["mc_currency"];
//                     $data['status'] = Transaction::$statusSuccess;
//                     $data['message'] = 'Payment Verified';
//                     $data['transaction_date'] = date('Y-m-d H:i:s');

//   $transaction = Transaction::create($data);

//                     $order = DB::table('orders')->where('id',1)->update([
//                       'active_status' => OrderStatusList::$received,
//                           'transaction_id'=>$transaction->id ?? 0,
//                                     ]);




                //     if ($paypalInfo["payment_status"] == 'Completed') {
                //         //send_mail($userData[1], 'Wait for Order Confirmation', 'Thanks for your order. We will let you know once your order confirm by partner on this email ID.');



                //         $transaction = Transaction::create($data);
                //         //Mark payment received
                //         $order->active_status = OrderStatusList::$received;
                //         $order->transaction_id = $transaction->id ?? 0;
                //         $order->save();

                //         // CommonHelper::setOrderStatus($order_status);
                //         CommonHelper::addSellerWiseOrder($order->id);
                //     //dd($request);
                //   $website_url = config('app.website_url');
                //   $parameters = [
                //   'store_id'            => 'knJR75qVeObWJwQM1nRqorBgzaAkEmZx9z8Vx0NyXK963PL27dYD5Gjl4GmBXxLA',
                //   'transaction_ref'        => $order->final_total,
                //   'custom_ref'         => $order->mobile,

                //  ];

                //     }

    }





        public function updateOrderTransactions(Request $request)
    {
      //  $paymentData = $request->all();
    //    $website_url = config('app.website_url');

// $order = DB::table('orders')->where('id', 1)->update([
//     'active_status' => 11,
//     'transaction_id' => 10,
// ]);

$order = DB::table('orders')->where('ref_id',$request->data['custom_ref'])->first();

                    $data['transaction_type'] = 'Transaction';
                    $data['user_id'] =$order->user_id;
                    //$data['payer_email']  = $userData[1];
                    $data['order_id'] =$order->id;
                    $data['type'] = 'TLYNC';
                    $data['txn_id'] = $request->data['reference'];
                    $data['payu_txn_id'] = "_";
                    $data['amount'] =$request->data['amount'];
                    //$data['currency_code'] = $paypalInfo["mc_currency"];
                    $data['status'] = Transaction::$statusSuccess;
                    $data['message'] = 'Payment Verified';
                    $data['transaction_date'] = date('Y-m-d H:i:s');

           if($request->result == "success"){
                $transaction = Transaction::create($data);

                    $order = DB::table('orders')->where('id',$order->id)->update([
                      'active_status' => OrderStatusList::$received,
                          'transaction_id'=>$transaction->id ?? 0,
                                    ]);
                                    return CommonHelper::responseSuccess(__('Payment has been successful'));
                 }
                 else{
                        return CommonHelper::responseError(__('Something went wrong'));
                 }




    }





    public function ipn(Request $request)
    {
        // Paypal posts the transaction data
        $paypalInfo = $request->all();
        Log::info("Paypal IPN : ", [$paypalInfo]);

        if (!empty($paypalInfo)) {
            // Validate and get the ipn response
            $paypal = new Paypal();
            $ipnCheck = $paypal->validate_ipn($paypalInfo);

            // Check whether the transaction is valid
            if ($ipnCheck) {

                $userData = explode('|', $paypalInfo['custom']);

                //for react app
                if (is_null($paypalInfo["item_number"]) && isset($userData[2])) {
                    $paypalInfo["item_number"] = $userData[2];
                }

                $order_id = $paypalInfo["item_number"];
                /* if its not numeric then it is for the wallet recharge */
                if (
                    $paypalInfo["payment_status"] == 'Completed' &&
                    !is_numeric($order_id) && strpos($order_id, "wallet-refill-user") !== false
                ) {
                    $temp = explode("-", $order_id); /* Order ID format for wallet refill >> wallet-refill-user-{user_id}-{system_time}-{3 random_number}  */
                    if (isset($temp[3]) && is_numeric($temp[3]) && !empty($temp[3] && $temp[3] != '')) {
                        $user_id = $temp[3];
                    } else {
                        $user_id = 0;
                    }
                    $amount = $paypalInfo["mc_gross"];
                    /* IPN for user wallet recharge */
                    $data['transaction_type'] = "wallet";
                    $data['user_id'] = $user_id;
                    $data['order_id'] = $order_id;
                    $data['type'] = "credit";
                    $data['txn_id'] = $paypalInfo["txn_id"];
                    $data['payu_txn_id'] = "";
                    $data['amount'] = $amount;
                    $data['status'] = "success";
                    $data['message'] = "Wallet refill successful";
                    $data['transaction_date'] = date('Y-m-d H:i:s');
                    //$this->transaction_model->add_transaction($data);


                    return false;
                } else {
                    /* IPN for normal Order  */
                    // Insert the transaction data in the database
                    $userData = explode('|', $paypalInfo['custom']);


                    $data['transaction_type'] = 'Transaction';
                    $data['user_id'] = $userData[0];
                    //$data['payer_email']  = $userData[1];
                    $data['order_id'] = $paypalInfo["item_number"];
                    $data['type'] = 'paypal';
                    $data['txn_id'] = $paypalInfo["txn_id"];
                    $data['payu_txn_id'] = "_";
                    $data['amount'] = $paypalInfo["mc_gross"];
                    //$data['currency_code'] = $paypalInfo["mc_currency"];
                    $data['status'] = Transaction::$statusSuccess;
                    $data['message'] = 'Payment Verified';
                    $data['transaction_date'] = date('Y-m-d H:i:s');

                    $order = Order::where('id', $data['order_id'])->first();
                    if ($paypalInfo["payment_status"] == 'Completed') {
                        //send_mail($userData[1], 'Wait for Order Confirmation', 'Thanks for your order. We will let you know once your order confirm by partner on this email ID.');



                        $transaction = Transaction::create($data);
                        //Mark payment received
                        $order->active_status = OrderStatusList::$received;
                        $order->transaction_id = $transaction->id ?? 0;
                        $order->save();


                        // CommonHelper::setOrderStatus($order_status);

                        CommonHelper::addSellerWiseOrder($order->id);
                    } else if (
                        $paypalInfo["payment_status"] == 'Expired' || $paypalInfo["payment_status"] == 'Failed'
                        || $paypalInfo["payment_status"] == 'Refunded' || $paypalInfo["payment_status"] == 'Reversed'
                    ) {
                        /* if transaction wasn't completed successfully then cancel the order and transaction */
                        $data['transaction_type'] = 'Transaction';
                        $data['user_id'] = $userData[0];
                        //$data['payer_email']  = $userData[1];
                        $data['order_id'] = $paypalInfo["item_number"];
                        $data['type'] = 'paypal';
                        $data['txn_id'] = $paypalInfo["txn_id"];
                        $data['payu_txn_id'] = "";
                        $data['amount'] = $paypalInfo["mc_gross"];
                        $data['currency_code'] = $paypalInfo["mc_currency"];
                        $data['status'] = $paypalInfo["payment_status"];
                        $data['message'] = 'Payment could not be completed due to one or more reasons!';
                        $data['transaction_date'] = date('Y-m-d H:i:s');
                        //$this->transaction_model->add_transaction($data);



                        $transaction = Transaction::create($data);
                        //Mark payment received
                        $order->active_status = OrderStatusList::$cancelled;
                        $order->transaction_id = $transaction->id ?? 0;
                        $order->save();

                        /*$order_status = array();
                        $order_status['order_id'] = $order->id;
                        $order_status['order_item_id'] = 0;
                        $order_status['status'] = "Order Cancelled";
                        $order_status['created_by'] = $order->user_id;
                        $order_status['user_type'] = OrderStatus::$userTypeUser;
                        CommonHelper::setOrderStatus($order_status);*/

                        CommonHelper::addSellerWiseOrder($order->id);
                    }
                }
            }
        }
    }
    /*Paypal End*/





    /*tlync Start*/
    public function tlyncPaymentUrl(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $app_name = Setting::get_value('app_name');

        $user = User::where('id', $request->user_id)->first();



        //  $order = Order::where('id', $request->order_id)->first();

        $order =   DB::table('orders')
            ->where('id', $request->order_id)
            ->first();



        if ($order) {

            header("Content-Type: html");

            $amount = $order->final_total;

            $data['user'] = $user;

            $data['order'] = $order;

            $data['payment_type'] = "tlync";
            // Set variables for paypal form
            $returnURL = url('customer/tlync_redirect/success');
            $cancelURL = url('customer/tlync_redirect/fail');
            $notifyURL = url('customer/tlync_ipn');
            $txn_id = time() . "-" . rand();
            // Get current user ID from the session
            $userID = $data['user']->id;
            $order_id = $data['order']->id;
            $payeremail = $data['user']->email;
            // $userID = $data['user']->id;

            $tlync = new TLYNC();
            // Add fields to paypal form
            $tlync->add_field('return', $returnURL);
            $tlync->add_field('cancel_return', $cancelURL);
            $tlync->add_field('notify_url', $notifyURL);
            $tlync->add_field('item_name', $app_name);
            $tlync->add_field('custom', $userID . '|' . $payeremail);
            $tlync->add_field('item_number', $order_id);
            $tlync->add_field('amount', $amount);

            // Render paypal form
            $tlync->tlync_auto_form();
        }
    }

    public function tlyncRedirect(Request $request)
    {
        $paypalInfo = $request->all();
        $website_url = config('app.website_url');

        Log::info("tlyncRedirect : ", [$tlyncInfo]);
        $order_status = Transaction::$statusFailed;
        if (!empty($tlyncInfo) && isset($tlyncInfo['payment_status']) && strtolower($tlyncInfo['payment_status']) == "completed") {
            $response['error'] = false;
            $response['message'] = "Payment Completed Successfully";
            $response['data'] = $tlyncInfo;
            $order_status = Transaction::$statusSuccess;
            // return CommonHelper::responseSuccessWithData($response['message'],$paypalInfo);
        } elseif (!empty($tlyncInfo) && isset($tlyncInfo['payment_status']) && strtolower($tlyncInfo['payment_status']) == "authorized") {
            $response['error'] = false;
            $response['message'] = "Your payment is has been Authorized successfully. We will capture your transaction within 30 minutes, once we process your order. After successful capture coins wil be credited automatically.";
            $response['data'] = $tlyncInfo;
            $order_status = Transaction::$statusSuccess;
            // return CommonHelper::responseSuccessWithData($response['message'],$paypalInfo);
        } elseif (!empty($tlyncInfo) && isset($tlyncInfo['payment_status']) && strtolower($tlyncInfo['payment_status']) == "pending") {
            $response['error'] = false;
            $response['message'] = "Your payment is pending and is under process. We will notify you once the status is updated.";
            $response['data'] = $tlyncInfo;
            // return CommonHelper::responseSuccessWithData($response['message'],$paypalInfo);
        } else {
            $response['error'] = true;
            $response['message'] = "Payment Cancelled / Declined ";
            $response['data'] = (isset($tlyncInfo)) ? $tlyncInfo : "";
            // return CommonHelper::responseError("Payment Cancelled / Declined");
        }

        echo "<html>
        <body>
        Redirecting...!
        </body>
        <script>
            //const parentOrigin = window.opener.location.origin;
            const parentOrigin = '" . $website_url . "';
            console.log('Parent origin:', parentOrigin);
            console.log('started')
            window.addEventListener('load', function(){
            console.log('loaded')
            window.opener.postMessage('" . $order_status . "',parentOrigin);
            window.close();
            });
        </script>
        </html>";
    }

    public function tlync_ipn(Request $request)
    {
        // Paypal posts the transaction data
        $tlyncInfo = $request->all();
        Log::info("Tlync IPN : ", [$tlyncInfo]);

        if (!empty($tlyncInfo)) {
            // Validate and get the ipn response
            $tlync = new TLYNC();
            $ipnCheck = $tlync->validate_ipn($tlyncInfo);

            // Check whether the transaction is valid
            if ($ipnCheck) {

                $userData = explode('|', $tlyncInfo['custom']);

                //for react app
                if (is_null($tlyncInfo["item_number"]) && isset($userData[2])) {
                    $tlyncInfo["item_number"] = $userData[2];
                }

                $order_id = $tlyncInfo["item_number"];
                /* if its not numeric then it is for the wallet recharge */
                if (
                    $tlyncInfo["payment_status"] == 'Completed' &&
                    !is_numeric($order_id) && strpos($order_id, "wallet-refill-user") !== false
                ) {
                    $temp = explode("-", $order_id); /* Order ID format for wallet refill >> wallet-refill-user-{user_id}-{system_time}-{3 random_number}  */
                    if (isset($temp[3]) && is_numeric($temp[3]) && !empty($temp[3] && $temp[3] != '')) {
                        $user_id = $temp[3];
                    } else {
                        $user_id = 0;
                    }
                    $amount = $tlyncInfo["mc_gross"];
                    /* IPN for user wallet recharge */
                    $data['transaction_type'] = "wallet";
                    $data['user_id'] = $user_id;
                    $data['order_id'] = $order_id;
                    $data['type'] = "credit";
                    $data['txn_id'] = $tlyncInfo["txn_id"];
                    $data['payu_txn_id'] = "";
                    $data['amount'] = $amount;
                    $data['status'] = "success";
                    $data['message'] = "Wallet refill successful";
                    $data['transaction_date'] = date('Y-m-d H:i:s');
                    //$this->transaction_model->add_transaction($data);


                    return false;
                } else {
                    /* IPN for normal Order  */
                    // Insert the transaction data in the database
                    $userData = explode('|', $tlyncInfo['custom']);


                    $data['transaction_type'] = 'Transaction';
                    $data['user_id'] = $userData[0];
                    //$data['payer_email']  = $userData[1];
                    $data['order_id'] = $tlyncInfo["item_number"];
                    $data['type'] = 'tlync';
                    $data['txn_id'] = $tlyncInfo["txn_id"];
                    $data['payu_txn_id'] = "";
                    $data['amount'] = $tlyncInfo["mc_gross"];
                    //$data['currency_code'] = $paypalInfo["mc_currency"];
                    $data['status'] = Transaction::$statusSuccess;
                    $data['message'] = 'Payment Verified';
                    $data['transaction_date'] = date('Y-m-d H:i:s');

                    $order = Order::where('id', $data['order_id'])->first();
                    if ($tlyncInfo["payment_status"] == 'Completed') {
                        //send_mail($userData[1], 'Wait for Order Confirmation', 'Thanks for your order. We will let you know once your order confirm by partner on this email ID.');



                        $transaction = Transaction::create($data);
                        //Mark payment received
                        $order->active_status = OrderStatusList::$received;
                        $order->transaction_id = $transaction->id ?? 0;
                        $order->save();


                        CommonHelper::setOrderStatus($order_status);

                        CommonHelper::addSellerWiseOrder($order->id);
                    } else if (
                        $tlyncInfo["payment_status"] == 'Expired' || $tlyncInfo["payment_status"] == 'Failed'
                        || $tlyncInfo["payment_status"] == 'Refunded' || $tlyncInfo["payment_status"] == 'Reversed'
                    ) {
                        /* if transaction wasn't completed successfully then cancel the order and transaction */
                        $data['transaction_type'] = 'Transaction';
                        $data['user_id'] = $userData[0];
                        //$data['payer_email']  = $userData[1];
                        $data['order_id'] = $tlyncInfo["item_number"];
                        $data['type'] = 'tlync';
                        $data['txn_id'] = $tlyncInfo["txn_id"];
                        $data['payu_txn_id'] = "";
                        $data['amount'] = $tlyncInfo["mc_gross"];
                        $data['currency_code'] = $tlyncInfo["mc_currency"];
                        $data['status'] = $tlyncInfo["payment_status"];
                        $data['message'] = 'Payment could not be completed due to one or more reasons!';
                        $data['transaction_date'] = date('Y-m-d H:i:s');
                        //$this->transaction_model->add_transaction($data);



                        $transaction = Transaction::create($data);
                        //Mark payment received
                        $order->active_status = OrderStatusList::$cancelled;
                        $order->transaction_id = $transaction->id ?? 0;
                        $order->save();

                        /*$order_status = array();
                        $order_status['order_id'] = $order->id;
                        $order_status['order_item_id'] = 0;
                        $order_status['status'] = "Order Cancelled";
                        $order_status['created_by'] = $order->user_id;
                        $order_status['user_type'] = OrderStatus::$userTypeUser;
                        CommonHelper::setOrderStatus($order_status);*/

                        CommonHelper::addSellerWiseOrder($order->id);
                    }
                }
            }
        }
    }
    /*tlync End*/






    public function addTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'device_type' => 'required',
            'app_version' => 'required',
            'payment_method' => 'required',
            'transaction_id' => 'required'
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $order = Order::withTrashed()->where('id', $request->order_id)->first();
        if (!$order) {
            return CommonHelper::responseError("Invalid Order Id");
        }

        if ($order->active_status == OrderStatusList::$received) {
            return CommonHelper::responseError("Your order has been already placed");
        }

        // Save Device details
        if ($request->device_type) {
            $app_usage = array();
            $app_usage['order_id'] = $order->id;
            $app_usage['device_type'] = $request->device_type;
            $app_usage['app_version'] = $request->app_version;
            AppUsage::create($app_usage);
        }

        $status = Transaction::$statusFailed;
        $txn_id = $request->transaction_id;

        if (
            isset($request->payment_method) && in_array(
                $request->payment_method,
                array(
                    Transaction::$paymentTypeRazorpay,
                    Transaction::$paymentTypePaystack,
                    Transaction::$paymentTypeStripe,
                    Transaction::$paymentTypePaytm
                )
            )
        ) {


            if ($request->payment_method == Transaction::$paymentTypeRazorpay) {
                $signatureIsVaid = TransactionHelper::verifyRazorpaySignature(
                    $request->razorpay_order_id,
                    $request->razorpay_payment_id,
                    $request->razorpay_signature
                );
                if (!$signatureIsVaid) {
                    $status = Transaction::$statusSuccess;
                }
            } else if ($request->payment_method == Transaction::$paymentTypePaystack) {

                $paystack = new Paystack();
                $payment = $paystack->verify_transaction($txn_id);

                Log::info("payment Paystack :  ", [$payment]);

                if (!empty($payment)) {
                    $payment = json_decode($payment, true);
                    if (isset($payment['data']['status']) && $payment['data']['status'] == 'success') {
                        $status = Transaction::$statusSuccess;
                    }
                }
            } else if ($request->payment_method == Transaction::$paymentTypeStripe) {

                try {

                    $stripe_secret_key = Setting::get_value('stripe_secret_key');
                    $stripe = new \Stripe\StripeClient(
                        $stripe_secret_key
                    );

                    $paymentIntent = $stripe->paymentIntents->retrieve(
                        $txn_id,
                        []
                    );
                    // "status" => "succeeded"
                    if ($paymentIntent->status === "succeeded") {
                        $status = Transaction::$statusSuccess;
                    }
                } catch (\Exception $e) {
                    Log::error("Stripe Error : ", [$e]);
                    return CommonHelper::responseError($e->getMessage());
                }
            } else if ($request->payment_method == Transaction::$paymentTypePaytm) {

                //$payment = Paytm::transaction_status($txn_id);
                $payment = Paytm::transaction_status($order->id);

                if (!empty($payment)) {
                    $payment = json_decode($payment, true);

                    if (isset($payment['body']['resultInfo']['resultCode']) && ($payment['body']['resultInfo']['resultCode'] == '01' && $payment['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS')) {
                        $status = Transaction::$statusSuccess;
                    } elseif (isset($payment['body']['resultInfo']['resultCode']) && ($payment['body']['resultInfo']['resultStatus'] == 'TXN_FAILURE')) {
                        $status = Transaction::$statusFailed;
                    } else if (isset($payment['body']['resultInfo']['resultCode']) && ($payment['body']['resultInfo']['resultStatus'] == 'PENDING')) {
                        //PENDING
                    } else {
                        $status = Transaction::$statusFailed;
                    }
                } else {
                    $status = Transaction::$statusFailed;
                }
            } else if ($request->payment_method == Transaction::$paymentTypePaypal) {

                $transaction_id = $request->transaction_id;

                $paypalClient = new PaypalClient();
                $server_output = $paypalClient->getPayment($transaction_id);
                $result = json_decode($server_output, 1);
                \Log::info('-------------Paypal start---------------');
                \Log::info('paypal result : ', [$result]);

                $status = Transaction::$statusFailed;

                if (isset($result['state']) && $result['state'] == 'approved') {
                    $status = Transaction::$statusSuccess;
                    $gateway_amount = $result['transactions'][0]['amount']['total'];
                }
            }

            $transactionData = array();
            $transactionData['user_id'] = $order->user_id;
            $transactionData['order_id'] = $order->id;
            $transactionData['type'] = $request->payment_method; // Razorpay / Paystack / Paypal
            $transactionData['txn_id'] = $txn_id;
            $transactionData['payu_txn_id'] = "";
            $transactionData['amount'] = $order->final_total;
            $transactionData['status'] = $status;
            $transactionData['message'] = "";
            $transactionData['transaction_date'] = date('Y-m-d H:i:s');
            $transaction = Transaction::create($transactionData);
        }

        if ($status == Transaction::$statusSuccess) {

            //Mark payment received
            $order->active_status = OrderStatusList::$received;
            $order->transaction_id = $transaction->id ?? 0;
            $order->save();

            /*$order_status = array();
            $order_status['order_id'] = $order->id;
            $order_status['order_item_id'] = 0;
            $order_status['status'] = OrderStatusList::$received;
            $order_status['created_by'] = $order->user_id;
            $order_status['user_type'] = OrderStatus::$userTypeUser;
            CommonHelper::setOrderStatus($order_status);*/

            CommonHelper::addSellerWiseOrder($order->id);

            return CommonHelper::responseSuccess("Order Placed Successfully");
        } else {
            return CommonHelper::responseError("Transaction Failed, Please try again!");
        }
    }

    public function updateOrderStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_item_id' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $order_item_id = $request->order_item_id;
        $order_item = OrderItem::select("*")->where("id", $order_item_id)->first();

        if (empty($order_item)) {
            return CommonHelper::responseError('Order Item Not found.');
        }

        if (isset($request->order_id)) {
            $id = $request->order_id;
        } else {
            $id = $order_item->order_id;
        }
        $order = Order::select("*")->where("id", $id)->first();
        if (empty($order)) {
            return CommonHelper::responseError('Order Not found.');
        }

        $user = User::select("*")->where('id', $order->user_id)->first();
        if (empty($user)) {
            return CommonHelper::responseError('User Not found.');
        }

        $postStatus = $request->status;
        $status = OrderStatusList::where('id', $postStatus)->first();
        if (empty($status)) {
            return CommonHelper::responseError('Status Not found.');
        }
        $selectedStatus = $status->status;
        if ($order_item->active_status == $postStatus) {
            return CommonHelper::responseError("This Order Item is already " . $selectedStatus . "!");
        }


        /* Cannot return order unless it is delivered */
        if (CommonHelper::isOrderItemReturned($order_item->active_status, $postStatus)) {
            return CommonHelper::responseError(__('cannot_return_order_unless_it_is_delivered'));
        }

        /* Could not update order status once cancelled or returned! */
        if (CommonHelper::isOrderItemCancelled($order_item_id)) {
            return CommonHelper::responseError(__('could_not_update_order_status_cancelled_or_returned'));
        }

        if (!empty($postStatus)) {

            /*if($postStatus == OrderStatusList::$received){
                $order->active_status = OrderStatusList::$received;
                $order->save();
            }*/

            if ($postStatus == OrderStatusList::$delivered) {

                if ($order->payment_method == Transaction::$paymentTypeCod) {

                    // Save Device details
                    if ($request->device_type) {
                        $app_usage = array();
                        $app_usage['order_id'] = $order->id;
                        $app_usage['device_type'] = $request->device_type;
                        $app_usage['app_version'] = $request->app_version;
                        AppUsage::create($app_usage);
                    }

                    $transactionData = array();
                    $transactionData['user_id'] = $order->user_id;
                    $transactionData['order_id'] = $order->id;
                    $transactionData['type'] = "COD";
                    $transactionData['txn_id'] = round(microtime(true) * 1000);
                    $transactionData['payu_txn_id'] = "";
                    $transactionData['amount'] = $order->total;
                    $transactionData['status'] = Transaction::$statusSuccess;
                    $transactionData['message'] = "";
                    $transactionData['transaction_date'] = date('Y-m-d H:i:s');
                    $transaction = Transaction::create($transactionData);
                    $order->transaction_id = $transaction->id ?? 0;
                }

                $order->active_status = OrderStatusList::$delivered;
                $order->save();

                $order_item->active_status = OrderStatusList::$delivered;
                $order_item->save();

                return CommonHelper::responseSuccess("Order Status Updated Successfully");
                /*Send Notification*/
            } else if ($postStatus == OrderStatusList::$cancelled) {
                DB::beginTransaction();
                try {

                    $itemNum = OrderItem::where("order_id", $order->id)->count();
                    $lastItemNum = 0;
                    if ($itemNum > 1) {
                        $lastItemNum = OrderItem::where("order_id", $order->id)->where('status', '!=', OrderStatusList::$cancelled)->count();
                    }
                    if ($itemNum == 1 || $lastItemNum == 1) {
                        $order_status = array();
                        $order_status['order_id'] = $order->id;
                        $order_status['order_item_id'] = $order_item->id;
                        $order_status['status'] = $postStatus;
                        $order_status['created_by'] = auth()->user()->id;
                        $order_status['user_type'] = OrderStatus::$userTypeUser;
                        CommonHelper::setOrderStatus($order_status);
                        $order->status = OrderStatusList::$cancelled;
                    }

                    $order->total = floatval($order->total) - floatval($order_item->sub_total);
                    $order->final_total = floatval($order->final_total) - floatval($order_item->sub_total);
                    $order->save();
                    $order_item->active_status = $postStatus;
                    $order_item->save();
                    if (isset($order->promo_code) && $order->promo_code != null && isset($order->promo_discount) && $order->promo_discount != null) {
                        $promo_code = explode("(", $order->promo_code);
                        $minimum_order_amount = PromoCode::where('promo_code', $promo_code[0])->first()->minimum_order_amount;
                        if (isset($minimum_order_amount) && $minimum_order_amount != null && $order->total < $minimum_order_amount) {
                            $order_id = $order->id;
                            CommonHelper::updateOrderPromoCode($order_id, $order->promo_discount);
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    // throw $e;
                    return CommonHelper::responseError(__('something_went_wrong'));
                }

                return CommonHelper::responseSuccessWithData("Order " . OrderStatusList::$orderCancelled . " Successfully", $order);
            } else {

                /*$order->active_status = $postStatus;
                  $order->save();*/
                $order_item->active_status = $postStatus;
                $order_item->save();
                return CommonHelper::responseSuccess("Order Status Updated Successfully");
            }
        }
    }

    public function getOrders(Request $request)
    {
        /*
         // this is use on post method.
         $limit = $request->get('limit',12);
        $offset = $request->get('offset',0);*/

        $limit = ($request->limit) ?? 12;
        $offset = ($request->offset) ?? 0;
        $page = $request->get('page', 0);

        $order_id = $request->order_id;
        $user_id = auth()->user()->id;


        //$where = !empty($order_id) ? " o.id = " . $order_id : "";
        //$sql = "select count(o.id) as total from orders o where user_id=" . $user_id . $where;

        $sql = Order::select(DB::raw("count(id) as total"))
            ->where("user_id", $user_id);
        if (!empty($order_id)) {
            $sql = $sql->where("id", $order_id);
        }

        if (isset($request->order_status_id) && $request->order_status_id != 0 && $request->order_status_id != "") {
            $sql = $sql->where("active_status", "=", $request->order_status_id);
        }

        if (isset($request->type)) {
            $activeTypeStatus = [OrderStatusList::$paymentPending, OrderStatusList::$received, OrderStatusList::$processed, OrderStatusList::$outForDelivery, OrderStatusList::$shipped];
            $previousTypeStatus = [OrderStatusList::$delivered, OrderStatusList::$cancelled, OrderStatusList::$returned];
            if ($request->type == Order::$activeType) {
                $sql = $sql->whereIn('orders.active_status', $activeTypeStatus);
            } else {
                $sql = $sql->whereIn('orders.active_status', $previousTypeStatus);
            }
        }

        $total = $sql->first();

        /*$sql = "select o.*,obt.message as bank_transfer_message,obt.status as bank_transfer_status,
                (select name from users u where u.id=o.user_id) as user_name
            from orders o
                LEFT JOIN order_bank_transfers obt ON obt.order_id=o.id
            where user_id=" . $user_id . $where . " ORDER BY date_added DESC LIMIT $offset,$limit";*/

        $sql = Order::select(
            "orders.*",
            'orders.address as order_address',
            'orders.mobile as order_mobile',
            'orders.id as order_id',
            "obt.message as bank_transfer_message",
            "obt.status as bank_transfer_status",
            DB::raw('(select name from users as u where u.id = orders.user_id) as user_name'),
            'address.address',
            'address.landmark',
            'address.area',
            'address.city',
            'address.state',
            'address.pincode',
            'address.country'
        )->from("orders as orders")
            ->leftJoin("order_bank_transfers as obt", "obt.order_id", "=", "orders.id")
            ->leftJoin('user_addresses as address', 'orders.address_id', '=', 'address.id')

            ->where("orders.user_id", "=", $user_id);
        if (!empty($order_id)) {
            $sql = $sql->where("orders.id", "=", $order_id);
        }

        if (isset($request->order_status_id) && $request->order_status_id != 0 && $request->order_status_id != "") {
            $sql = $sql->where("orders.active_status", "=", $request->order_status_id);
        }

        if (isset($request->type)) {
            $activeTypeStatus = [OrderStatusList::$paymentPending, OrderStatusList::$received, OrderStatusList::$processed, OrderStatusList::$outForDelivery, OrderStatusList::$shipped];
            $previousTypeStatus = [OrderStatusList::$delivered, OrderStatusList::$cancelled, OrderStatusList::$returned];
            if ($request->type == Order::$activeType) {
                $sql = $sql->whereIn('orders.active_status', $activeTypeStatus);
            } else {
                $sql = $sql->whereIn('orders.active_status', $previousTypeStatus);
            }
        }
        $res = $sql->orderBy("orders.id", "DESC")->skip($offset)->take($limit)->get();

        $res = $res->makeHidden(['image', 'updated_at', 'deleted_at', 'current_status']);

        //$res = $sql->orderBy("orders.id","DESC")->offset($offset)->limit($limit)->get(); // when num of row is 0 then give error.
        //$res = $sql->orderBy("orders.id","DESC")->paginate(12);

        $i = 0;
        foreach ($res as $key => $row) {
            $res[$key]->address = $row->address . " " . $row->landmark . " " . $row->area . " " . $row->city . " " . $row->state . "-" . $row->pincode . " " . $row->country;

            // echo "meri ek tang nakli hain me hoki ka bohot bada khiladi hun";
            $final_sub_total = 0;
            $sub_total = 0;

            $row->promo_code = explode('(', $row->promo_code)[0];

            if ($row->discount > 0) {

                $discounted_amount = $row->total * $row->discount / 100;
                $final_total = $row->total - $discounted_amount;
                $discount_in_rupees = $row->total - $final_total;
            } else {
                $discount_in_rupees = 0;
            }

            $res[$i]['discount_rupees'] = $discount_in_rupees;
            $final_total = ceil($res[$i]['final_total']);
            $res[$i]['final_total'] = $final_total;
            //$res[$i]['created_at'] = Carbon::createFromFormat('Y-m-d',date('Y-m-d',strtotime($res[$i]['created_at'])))->format('Y-m-d');
            $res[$i]['created_at'] = date('Y-m-d', strtotime($res[$i]['created_at']));

            $res[$i]['bank_transfer_message'] = !empty($res[$i]['bank_transfer_message']) ? $res[$i]['bank_transfer_message'] : "";
            $res[$i]['bank_transfer_status'] = !empty($res[$i]['bank_transfer_status']) ? $res[$i]['bank_transfer_status'] : 0;

            $orderStatus = orderStatus::where('order_id', $row['id'])->get();
            $data = array();
            foreach ($orderStatus as $status) {
                $subData = array();
                array_push($subData, $status->status, $status->created_at);
                array_push($data, $subData);
            }
            $res[$i]['status'] = json_encode($data);



            $items = OrderItem::with('images')->select(
                'oi.*',
                'v.id as variant_id',
                'p.name',
                'p.image',
                'p.manufacturer',
                'p.made_in',
                'p.return_status',
                'p.return_days',
                'p.cancelable_status',
                'p.till_status',
                'v.measurement',
                DB::raw('(select short_code from units as u where u.id = v.stock_unit_id) as unit'),
                'co.name as country_made_in',
                's.name as seller_name',
                's.formatted_address as seller_address',
                's.place_name as seller_place_name',
                's.latitude as seller_latitude',
                's.longitude as seller_longitude'
            )
                ->from('order_items as oi')
                ->leftJoin('product_variants as v', 'oi.product_variant_id', '=', 'v.id')
                ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
                ->leftJoin('sellers as s', 'oi.seller_id', '=', 's.id')
                ->leftJoin("countries as co", "p.made_in", "=", "co.id")
                ->where('oi.order_id', '=', $row['id'])
                ->orderBy('oi.id', 'ASC')
                ->get();


            foreach ($items as $subkey => $item) {
                //dd($item);
                $taxed = ProductHelper::getTaxableAmount($item->product_variant_id);

                $items[$subkey]->made_in = $item->country_made_in ?? "";
                $items[$subkey]->created_at = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($item->created_at)))->format('Y-m-d');
                $items[$subkey]->price = CommonHelper::doubleNumber($taxed->taxable_amount ?? $item->price);
                $cancelableStatusList = array(OrderStatusList::$received, OrderStatusList::$processed, OrderStatusList::$shipped, OrderStatusList::$outForDelivery);

                /*if( intval($item->active_status) <= intval($item->till_status) && in_array($item->active_status, $cancelableStatusList)){}*/
                if (intval($row->active_status) <= intval($item->till_status) && in_array($row->active_status, $cancelableStatusList)) {
                    $items[$subkey]->cancelable_status = 1;
                } else {
                    $items[$subkey]->cancelable_status = 0;
                }

                $created_at = date_create(date('Y-m-d', strtotime($row->created_at)));
                $current_data = date_create(date('Y-m-d'));
                $order_days = abs(date_diff($created_at, $current_data)->format('%R%a'));

                if (intval($order_days) <= intval($item->return_days) && intval($row->active_status) == OrderStatusList::$delivered) {
                    $items[$subkey]->return_status = 1;
                } else {
                    $items[$subkey]->return_status = 0;
                }
            }
            $items = $items->makeHidden(['image', 'images', 'updated_at', 'deleted_at', 'status', 'current_status', 'country_made_in']);
            /*for ($j = 0; $j < $items->count(); $j++) {


                // unset($res[$i][$j]['status']);
                if ($res[$i]['items'][$j]['standard_shipping'] == 1) {
                    $res[$i]['items'][$j]['shipping_method'] = 'standard';
                    $res[$i]['status'] = "";
                    $res[$i]['active_status'] = "";
                    $res[$i]['items'][$j]['status'] = "";

                    $order_tracking = $this->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');

                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                        $sub_total += $res[$i]['items'][$j]['sub_total'];
                    }

                    if (!empty($res[$i]['items'][$j]['status'])) {
                        if (count($res[$i]['items'][$j]['status']) > 1) {
                            if (in_array("awaiting_payment", $res[$i]['items'][$j]['status'][0]) && in_array("received", $res[$i]['items'][$j]['status'][1])) {
                                unset($res[$i]['items'][$j]['status'][0]);
                            }
                            $res[$i]['items'][$j]['status'] = array_values($res[$i]['items'][$j]['status']);
                        }
                    } else {
                        $res[$i]['items'][$j]['status'] = array();
                    }

                    $res[$i]['items'][$j]['delivery_boy_id'] = (!empty($res[$i]['items'][$j]['delivery_boy_id'])) ? $res[$i]['items'][$j]['delivery_boy_id'] : "";
                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $this->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $item_details = $this->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                    $sql = "SELECT id from return_requests where product_variant_id = " . $res[$i]['items'][$j]['variant_id'] . " AND user_id = " . $user_id;
                    $this->db->sql($sql);
                    $return_request = $this->db->getResult();

                    $order_tracking_data = $this->get_data(['*'], 'order_item_id=' . $res[$i]['items'][$j]['id'], 'order_trackings');
                    if (empty($order_tracking_data)) {
                        $res[$i]['items'][$j]['active_status'] = 'Order not created';
                        $res[$i]['items'][$j]['shipment_id'] = "";
                        $res[$i]['items'][$j]['awb_code'] = "";
                        $res[$i]['items'][$j]['pickup_status'] = "";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    } else if (!empty($order_tracking_data[0]['shipment_id']) && empty($order_tracking_data[0]['awb_code'])) {
                        $res[$i]['items'][$j]['active_status'] = 'AWb not generated';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = "";
                        $res[$i]['items'][$j]['pickup_status'] = "";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    } else if (!empty($order_tracking_data[0]['shipment_id']) && !empty($order_tracking_data[0]['awb_code']) && $order_tracking_data[0]['pickup_status'] == 0 && $order_tracking_data[0]['is_canceled'] == 0) {
                        $res[$i]['items'][$j]['active_status'] = 'Send request for pickup pending';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = isset($order_tracking_data[0]['awb_code']) ? $order_tracking_data[0]['awb_code'] : "0";
                        $res[$i]['items'][$j]['pickup_status'] = "1";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    } else if ($order_tracking_data[0]['is_canceled'] == 1 && !empty($order_tracking_data[0]['shipment_id']) && !empty($order_tracking_data[0]['awb_code']) && $order_tracking_data[0]['pickup_status'] == 1) {
                        $res[$i]['items'][$j]['active_status'] = 'Order is canclled';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = isset($order_tracking_data[0]['awb_code']) ? $order_tracking_data[0]['awb_code'] : "0";
                        $res[$i]['items'][$j]['pickup_status'] = "1";
                        $res[$i]['items'][$j]['is_canceled'] = "1";
                    } else {
                        $res[$i]['items'][$j]['active_status'] = 'Order Ready for tracking';
                        $res[$i]['items'][$j]['shipment_id'] = $order_tracking_data[0]['shipment_id'];
                        $res[$i]['items'][$j]['awb_code'] = isset($order_tracking_data[0]['awb_code']) ? $order_tracking_data[0]['awb_code'] : "0";
                        $res[$i]['items'][$j]['pickup_status'] = "1";
                        $res[$i]['items'][$j]['is_canceled'] = "";
                    }
                } else {
                    $res[$i]['items'][$j]['shipping_method'] = 'local';
                    $res[$i]['items'][$j]['status'] = (!empty($res[$i]['items'][$j]['status'])) ? json_decode($res[$i]['items'][$j]['status']) : array();
                    if ($res[$i]['items'][$j]['active_status'] != 'cancelled' && $res[$i]['items'][$j]['active_status'] != 'returned') {
                        $final_sub_total += $res[$i]['items'][$j]['sub_total'];
                        $sub_total += $res[$i]['items'][$j]['sub_total'];
                    }
                    if (!empty($res[$i]['items'][$j]['status'])) {
                        if (count($res[$i]['items'][$j]['status']) > 1) {
                            if (in_array("awaiting_payment", $res[$i]['items'][$j]['status'][0]) && in_array("received", $res[$i]['items'][$j]['status'][1])) {
                                unset($res[$i]['items'][$j]['status'][0]);
                            }
                            $res[$i]['items'][$j]['status'] = array_values($res[$i]['items'][$j]['status']);
                        }
                    } else {
                        $res[$i]['items'][$j]['status'] = array();
                    }

                    $res[$i]['items'][$j]['delivery_boy_id'] = (!empty($res[$i]['items'][$j]['delivery_boy_id'])) ? $res[$i]['items'][$j]['delivery_boy_id'] : "";
                    if (!empty($res[$i]['items'][$j]['seller_id'])) {
                        $seller_info = $this->get_data($columns = ['name', 'store_name'], "id=" . $res[$i]['items'][$j]['seller_id'], 'seller');
                        $res[$i]['items'][$j]['seller_name'] = $seller_info[0]['name'];
                        $res[$i]['items'][$j]['seller_store_name'] = $seller_info[0]['store_name'];
                    } else {
                        $res[$i]['items'][$j]['seller_id'] = "";
                        $res[$i]['items'][$j]['seller_name'] = "";
                        $res[$i]['items'][$j]['seller_store_name'] = "";
                    }
                    $item_details = $this->get_product_by_variant_id2($res[$i]['items'][$j]['product_variant_id']);
                    $res[$i]['items'][$j]['return_days'] = ($item_details['return_days'] != "") ? $item_details['return_days'] : '0';
                    $res[$i]['items'][$j]['image'] = DOMAIN_URL . $res[$i]['items'][$j]['image'];
                    $sql = "SELECT id from return_requests where product_variant_id = " . $res[$i]['items'][$j]['variant_id'] . " AND user_id = " . $user_id;
                    $this->db->sql($sql);
                    $return_request = $this->db->getResult();
                    if (empty($return_request)) {
                        $res[$i]['items'][$j]['applied_for_return'] = false;
                    } else {
                        $res[$i]['items'][$j]['applied_for_return'] = true;
                    }
                    $res[$i]['items'][$j]['shipment_id'] = "0";
                }
            }*/

            /*$items = $items->toArray();
            $items =  array_map("array_filter",$items);
            $items = array_filter($items);*/

            $res[$i]['items'] = $items;
            $res[$i]['status'] = json_decode($res[$i]['status']);
            $res[$i]['final_total'] = strval($row['final_total']);
            $res[$i]['total'] = strval($row['total']);
            $i++;
        }
        //$orders = $order = array();
        if (!empty($res) && $total->total !== 0) {
            /*$orders = $res->toArray();
             $orders =  array_map('array_filter',$orders);
            $orders = array_filter($orders);*/
            return CommonHelper::responseWithData($res, $total->total);
        } else {
            return CommonHelper::responseError(__('no_orders_found'));
        }
    }

    public function generateOrderInvoice(Request $request)
    {
        $data = CommonHelper::getOrderDetails($request->order_id);
        if (!$data["order"]) {
            return CommonHelper::responseError("Order Not found!");
        }
        $invoice = CommonHelper::generateOrderInvoice($data);
        return CommonHelper::responseWithData($invoice);
    }

    public function downloadOrderInvoice(Request $request)
    {
        return CommonHelper::downloadOrderInvoice($request->order_id);
    }


    public function getOrders_new(Request $request)
    {

        $limit = ($request->limit) ?? 12;
        $offset = ($request->offset) ?? 0;
        $order_id = $request->order_id;
        $user_id = auth()->user()->id;

        //$where = !empty($order_id) ? " o.id = " . $order_id : "";
        //$sql = "select count(o.id) as total from orders o where user_id=" . $user_id . $where;

        $sql = Order::select(DB::raw("count(oi.id) as total"))->leftJoin('order_items as oi', 'oi.order_id', '=', 'orders.id')
            ->where("orders.user_id", $user_id);
        if (!empty($order_id)) {
            $sql = $sql->where("oi.id", $order_id);
        }
        if (isset($request->order_status_id) && $request->order_status_id != 0 && $request->order_status_id != "") {
            $sql = $sql->where("oi.active_status", "=", $request->order_status_id);
        }

        $total = $sql->first();


        $sql = Order::select(
            "orders.*",
            "orders.id as order_id",
            "obt.message as bank_transfer_message",
            "obt.status as bank_transfer_status",
            DB::raw('(select name from users as u where u.id = orders.user_id) as user_name'),
            'address.address',
            'address.landmark',
            'address.area',
            'address.city',
            'address.state',
            'address.pincode',
            'address.country',

            'oi.*',
            'v.id as variant_id',
            'p.name',
            'p.image',
            'p.manufacturer',
            'p.made_in',
            'p.return_status',
            'p.return_days',
            'p.cancelable_status',
            'p.till_status',
            'v.measurement',
            DB::raw('(select short_code from units as u where u.id = v.stock_unit_id) as unit'),
            'os.status as current_status',
            'os.id as order_status_id',
            'co.name as country_made_in',
            's.name as seller_name'
        )->from("orders as orders")

            ->leftJoin('order_items as oi', 'oi.order_id', '=', 'orders.id')

            ->leftJoin('product_variants as v', 'oi.product_variant_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('sellers as s', 'oi.seller_id', '=', 's.id')
            ->leftJoin("countries as co", "p.made_in", "=", "co.id")
            ->leftJoin('order_status_lists as os', 'oi.active_status', '=', 'os.id')

            ->leftJoin("order_bank_transfers as obt", "obt.order_id", "=", "orders.id")
            ->leftJoin('user_addresses as address', 'orders.address_id', '=', 'address.id')

            ->where("orders.user_id", "=", $user_id);
        if (!empty($order_id)) {
            $sql = $sql->where("orders.id", "=", $order_id);
        }

        if (isset($request->order_status_id) && $request->order_status_id != 0 && $request->order_status_id != "") {
            $sql = $sql->where("oi.active_status", "=", $request->order_status_id);
        }

        $res = $sql->orderBy("orders.id", "DESC")->skip($offset)->take($limit)->get();
        $res = $res->makeHidden(['image', 'images', 'updated_at', 'deleted_at', 'current_status', 'status', 'country_made_in', 'order_status_id']);

        $i = 0;
        foreach ($res as $key => $row) {
            $res[$key]->active_status = $row->current_status ?? "";
            $res[$key]->address = $row->address . " " . $row->landmark . " " . $row->area . " " . $row->city . " " . $row->state . "-" . $row->pincode . " " . $row->country;
            $res[$key]->active_status = $row->current_status ?? "";
            $res[$key]->made_in = $row->country_made_in ?? "";
            $res[$key]->created_at = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($row->created_at)))->format('Y-m-d');

            if ($row->order_status_id == $row->till_status) {
                $res[$key]->cancelable_status = 1;
            } else {
                $res[$key]->cancelable_status = 0;
            }

            $created_at = date_create(date('Y-m-d', strtotime($row->created_at)));
            $current_data = date_create(date('Y-m-d'));
            $order_days = abs(date_diff($created_at, $current_data)->format('%R%a'));
            if ($order_days <= $row->return_days) {
                $res[$key]->return_status = 1;
            } else {
                $res[$key]->return_status = 0;
            }

            if ($row->discount > 0) {
                $discounted_amount = $row->total * $row->discount / 100;
                $final_total = $row->total - $discounted_amount;
                $discount_in_rupees = $row->total - $final_total;
            } else {
                $discount_in_rupees = 0;
            }
            $res[$i]['discount_rupees'] = $discount_in_rupees;
            $final_total = ceil($res[$i]['final_total']);
            $res[$i]['final_total'] = $final_total;
            $res[$i]['created_at'] = date('Y-m-d', strtotime($res[$i]['created_at']));
            $res[$i]['bank_transfer_message'] = !empty($res[$i]['bank_transfer_message']) ? $res[$i]['bank_transfer_message'] : "";
            $res[$i]['bank_transfer_status'] = !empty($res[$i]['bank_transfer_status']) ? $res[$i]['bank_transfer_status'] : "0";
            $res[$i]['image_url'] = CommonHelper::getImage($res[$i]['image']);
            $res[$i]['status'] = json_decode($res[$i]['status']);
            $res[$i]['final_total'] = strval($row['final_total']);
            $res[$i]['total'] = strval($row['total']);
            $i++;
        }

        if (!empty($res) && $total->total !== 0) {
            return CommonHelper::responseWithData($res, $total->total);
        } else {
            return CommonHelper::responseError(__('no_orders_found'));
        }
    }

    /*Paytm*/
    public function generatePaytmChecksum(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'amount' => 'required',
            'website' => 'required',
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $credentials = Paytm::get_credentials();
        $paytm_merchant_id = Setting::get_value('paytm_merchant_id');
        $paytm_params["MID"] = $paytm_merchant_id;

        $paytm_params["ORDER_ID"] = $request->order_id;
        $paytm_params["TXN_AMOUNT"] = $request->amount;
        $paytm_params["CUST_ID"] = auth()->user()->id;
        // $paytm_params["INDUSTRY_TYPE_ID"] = $this->input->post('industry_type', true);
        // $paytm_params["CHANNEL_ID"] = $this->input->post('channel_id', true);

        $paytm_params["WEBSITE"] = $request->get('website', 'DEFAULT');
        $paytm_params["CALLBACK_URL"] = $credentials['url'] . "theia/paytmCallback?ORDER_ID=" . $paytm_params["ORDER_ID"];

        /**
         * Generate checksum by parameters we have
         * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys
         */
        $paytm_checksum = Paytm::generateSignature($paytm_params, $paytm_merchant_id);

        Log::info("paytm_checksum : ", [$paytm_checksum]);
        $response = array();
        if (!empty($paytm_checksum)) {
            $response['order id'] = $paytm_params["ORDER_ID"];
            $response['data'] = $paytm_params;
            $response['signature'] = $paytm_checksum;
            return CommonHelper::responseSuccessWithData('Checksum created successfully', $response);
        } else {
            return CommonHelper::responseError('Data not found!');
        }
    }

    public function generatePaytmTxnToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $credentials = Paytm::get_credentials();





        $order_id = $request->order_id;
        $amount = $request->amount;
        $user_id = auth()->user()->id;
        $paytmParams = array();

        $paytmParams["body"] = array(
            "requestType" => "Payment",
            "mid" => $credentials['paytm_merchant_id'],
            "websiteName" => "WEBSTAGING",
            "orderId" => $order_id,
            "callbackUrl" => $credentials['url'] . "theia/paytmCallback?ORDER_ID=" . $order_id,
            "txnAmount" => array(
                "value" => $amount,
                "currency" => "INR",
            ),
            "userInfo" => array(
                "custId" => $user_id,
            ),
        );



        /*
         * Generate checksum by parameters we have in body
         * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys
         */
        $checksum = Paytm::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $credentials['paytm_merchant_key']);



        $paytmParams["head"] = array(
            "signature" => $checksum
        );

        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        // dd($post_data);

        /* for Staging */
        $url = $credentials['url'] . "/theia/api/v1/initiateTransaction?mid=" . $credentials['paytm_merchant_id'] . "&orderId=" . $order_id;

        /* for Production */
        // $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=YOUR_MID_HERE&orderId=ORDERID_98765";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        $paytm_response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        if (isset($error_msg)) {
            Log::info("curl error message : ", [$error_msg]);
        }

        Log::info("paytm_response : ", [$paytm_response]);

        $response = array();
        if (!empty($paytm_response)) {
            $paytm_response = json_decode($paytm_response, true);
            if (isset($paytm_response['body']['resultInfo']['resultMsg']) && ($paytm_response['body']['resultInfo']['resultMsg'] == "Success" || $paytm_response['body']['resultInfo']['resultMsg'] == "Success Idempotent")) {
                $response['txn_token'] = $paytm_response['body']['txnToken'];
                $response['paytm_response'] = $paytm_response;

                return CommonHelper::responseSuccessWithData('Transaction token generated successfully', $response);
            } else {
                $response['message'] = $paytm_response['body']['resultInfo']['resultMsg'];
                $response['txn_token'] = "";
                $response['paytm_response'] = $paytm_response;

                return CommonHelper::responseError($paytm_response['body']['resultInfo']['resultMsg']);
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Could not generate transaction token. Try again!";
            $response['txn_token'] = "";
            $response['paytm_response'] = $paytm_response;
            return CommonHelper::responseError("Could not generate transaction token. Try again!");
        }
    }
    /*Paytm*/
}
