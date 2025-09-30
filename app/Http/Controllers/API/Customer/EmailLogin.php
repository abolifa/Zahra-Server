<?php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserToken;
use App\Helpers\CommonHelper;

class EmailLogin extends Controller
{
    public function emailLogin(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        // Attempt to find the user
        $user = User::where('email', $request->email)->first();

        // Verify user and password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return CommonHelper::responseError(__('Invalid email or password'));
        }

        // Authenticate and generate token
        Auth::login($user);
        $accessToken = $user->createToken('authToken')->accessToken;

        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'profile' => $user->profile,
                'balance' => $user->balance,
                'referral_code' => $user->referral_code ?? '',
                'status' => intval($user->status) ?? 0,
            ],
            'access_token' => $accessToken,
        ];

        // Handle FCM token
        if ($request->has('fcm_token')) {
            $token = UserToken::updateOrCreate(
                ['fcm_token' => $request->fcm_token],
                [
                    'user_id' => $user->id,
                    'type' => 'customer',
                ]
            );
        }

        return CommonHelper::responseWithData($response);
    }
}
