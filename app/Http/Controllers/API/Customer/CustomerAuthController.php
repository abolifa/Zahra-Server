<?php

// namespace App\Http\Controllers\Api\Customer;
namespace App\Http\Controllers\API\Customer;

use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;

class CustomerAuthController extends Controller
{


    public function userExists(Request $request)
    {
        $user = DB::table('users')->where('mobile', $request->mobile)->first();
        //    dd($user);
        if (isset($user) && $user != null) {
            return CommonHelper::responseSuccess(__('User exists with this mobile'));
        } else {
            return CommonHelper::responseError(__('User does not exists with this mobile'));
        }
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            // 'password' => 'required',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        try {
            // Check if user exists
            $user = User::where('mobile', $request->mobile)->first();

            if (!$user) {
                return CommonHelper::responseError(__('user_not_found'));
            }

            // // Verify password
            // if (!Hash::check($request->password, $user->password)) {
            //     return CommonHelper::responseError(__('invalid_credentials'));
            // }

            // Log the user in
            Auth::login($user);
            $accessToken = $user->createToken('authToken')->accessToken;

            return CommonHelper::responseWithData([
                'user' => $user,
                'access_token' => $accessToken,
            ]);
        } catch (\Exception $e) {
            Log::error('Login : ' . $e->getMessage());
            return CommonHelper::responseError($e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        if (isset($request->fcm_token)) {
            $userToken = UserToken::where('type', 'customer')
                ->where('user_id', $request->user()->id)
                ->where('fcm_token', $request->fcm_token)->first();
            if ($userToken) {
                $userToken->delete();
            }
        }

        $token = $request->user()->token();
        $token->revoke();

        return CommonHelper::responseSuccess(__('you_have_been_successfully_logged_out'));
    }


    public function notLogin()
    {
        return CommonHelper::responseError(__('unauthorized'));
    }


    public function deleteAccount(Request $request)
    {
        $requestData = $request->all();
        $validator = Validator::make($requestData, [
            'auth_uid' => 'required',
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }
        try {
            if ($request->auth_uid != 123456) {

                $factory = (new Factory)->withServiceAccount(base_path('config/firebase.json'));
                $auth = $factory->createAuth();
                $user = $auth->getUser($request->auth_uid);

                if ($user->uid != $request->auth_uid) {
                    return CommonHelper::responseError("Your account already deleted!");
                }
            }

            $user_id = auth()->user()->id;
            $user = User::where('id', $user_id)->first();

            if ($user->mobile == '88822269298888' && isDemoMode()) {
                return CommonHelper::responseError("This function is not available in demo mode!");
            }

            $user->delete();
            return CommonHelper::responseSuccess("Your account deleted successfully!");
        } catch (\Exception $e) {
            Log::error('Login : ' . $e->getMessage());
            return CommonHelper::responseError($e->getMessage());
        }
    }

    public function editProfile(Request $request)
    {

        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            //'email' => 'required|unique:users,email,'.$user->id,

        ], []);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        //dd($request->all());

        $user->name = $request->name;
        $user->email = $request->email;

        /*if(isset($request->mobile)) {
            $user->mobile = $request->mobile;
        }*/

        if ($request->hasFile('profile')) {
            $file = $request->file('profile');

            $fileName = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();

            $image = Storage::disk('public')->put('customers/' . $fileName, file_get_contents($file));
            $user->profile = $image;
        }

        if ($user->status == 2) {
            if (isset($request->referral_code)) {
                $validCode = User::where('status', 1)
                    ->where('referral_code', $request->referral_code)->first();
                if ($validCode) {
                    $user->friends_code = $request->referral_code;
                }
            }
            $user->status = 1;
            CommonHelper::setDefaultMailSetting($user->id, 0);
        }

        $user->save();

        return CommonHelper::responseSuccess(__('profile_updated_successfully'));
    }

    public function changePassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $user = auth()->user();
        $user->password = bcrypt($request->password);
        $user->save();

        return CommonHelper::responseSuccess(__('password_updated_successfully'));
    }

    public function uploadProfile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'profile' => 'required',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $user = auth()->user();
        if ($request->hasFile('profile')) {
            $file = $request->file('profile');
            $image = Storage::disk('public')
                ->putFileAs('customers', $file, $user->id . ".jpg");
            $user->profile = $image;
            $user->save();
        }
        return CommonHelper::responseSuccess(__('profile_updated_successfully'));
    }

    public function addFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required',
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }
        $user_id = $request->user('api-customers') ? $request->user('api-customers')->id : '';

        $token = UserToken::where("fcm_token", $request->fcm_token)->first();

        if (isset($user_id) && $user_id != "" && !empty($token) && ($token->user_id == 0 || $token->user_id == "")) {
            $token->user_id = $user_id;
            $token->save();
            return CommonHelper::responseSuccess(__('token_updated_successfully'));
        } else {
            UserToken::firstOrCreate([
                'user_id' => 0,
                'type' => 'customer',
                'fcm_token' => $request->fcm_token
            ]);
            return CommonHelper::responseSuccess(__('token_added_successfully'));
        }
    }

    public function updateFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        UserToken::firstOrCreate([
            'user_id' => auth()->user()->id,
            'type' => 'customer',
            'fcm_token' => $request->fcm_token
        ]);

        return CommonHelper::responseSuccess(__('token_updated_successfully'));
    }

    public function getLoginUserDetails(Request $request)
    {
        $user_id = $request->user('api-customers') ? $request->user('api-customers')->id : '';
        $total = Cart::select(DB::raw('COUNT(carts.id) AS total'))->Join('products', 'carts.product_id', '=', 'products.id')->where('carts.save_for_later', '=', 0)->where('user_id', '=', $user_id)->first();
        $total = $total->makeHidden(['image_url']);
        $user = User::select('id', 'name', 'email', 'country_code', 'mobile', 'profile', 'balance', 'referral_code', 'status')->where('id', $user_id)->first();
        if (!empty($user)) {
            return Response::json(array('status' => 1, 'message' => 'success', 'total' => 1, 'cart_items_count' => $total->total, 'user' => $user));
        } else {
            return CommonHelper::responseError(__('unauthorized'));
        }
    }


    public function register(Request $request)
    {

        $requestData = $request->all();
        $validator = Validator::make($requestData, [
            'name' => 'required|min:3',
            'mobile' => 'nullable|unique:users',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        try {
            // Hash the password
            $requestData['password'] = bcrypt($requestData['password']);

            // Generate referral code
            $referral_code = strtoupper(substr(sha1(microtime()), 0, 6));

            // Create the user
            $user = new User();
            $user->auth_uid = Str::uuid();
            $user->name = $request->name;
            $user->email = $request->email ?? '';
            $user->profile = '';
            $user->referral_code = $referral_code;
            $user->status = 1;
            $user->country_code = $request->get('country_code', '');
            $user->mobile = $request->mobile ?? '';

            // Remove auth_uid dependency
            $user->password = $requestData['password'];
            $user->save();

            return CommonHelper::responseWithData(__('user_register_successfully'));
        } catch (\Exception $e) {
            return CommonHelper::responseError(__('user_not_found'));
        }
    }
}
