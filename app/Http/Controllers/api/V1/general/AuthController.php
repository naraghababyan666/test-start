<?php

namespace App\Http\Controllers\api\V1\general;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\Concerns\Has;
use PHPUnit\Exception;

class AuthController extends Controller
{

    public function registration(UserRequest $request)
    {
        try {
            if($request['role_id'] != Role::SUPER_ADMIN && $request['role_id'] != Role::MODERATOR){
                $newUser = User::create([
                    'first_name' => $request['first_name'],
                    'last_name' => $request['last_name'],
                    'email' => $request['email'],
                    'password' => Hash::make($request['password']),
                    'role_id' => $request['role_id'],
                ]);
                if (isset($request["company_name"])) {
                    $newUser->company_name = $request["company_name"];
                }
                if (isset($request["tax_identity_number"])) {
                    $newUser->tax_identity_number = $request["tax_identity_number"];
                }
                $newUser->save();
                Auth::login($newUser);
                $token = $newUser->createToken($request["email"], ['server:update']);
                $newUser["api_token"] = $token->plainTextToken;
                $data = [
                    'success' => true,
                    'data' => new UserResource($newUser),
                ];
                return
                    response($data)->setStatusCode(200)->header('Status-Code', '200');
            }else{
                return response()->json(['success' => false, 'message' => 'Permission denied!']);
            }
        } catch (Exception $e) {

            throw new HttpResponseException(response()->json([
                'message' => $e->getMessage(),
            ],  $e->getCode())->header('Status-Code', $e->getCode()));
        }
    }

    public function  logout(){
        Auth::user()->tokens()->where('id', Auth::id())->delete();
        $user = Auth()->user();
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        return response()->json([   'success' => true,
                                   'message' => __("messages.log_out"),])->header('Status-Code', '200');
    }

    public function checkOldPassword(Request $request){
        $user = Auth::user();
        if(Hash::check($request->all()['password'], $user['password'])){
            return response()->json(['success' => true,"message"=> __('validation.valid-password')]);
        }
        return response()->json(['success' => false, 'message' => __('validation.invalid-password')]);
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), ['email' => 'required|email', 'password' => 'required']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "errors" => $validator->errors()
            ],)->header('Status-Code', 200);
        }
        $validUser = Auth::attempt(['email' => $request["email"], 'password' => $request["password"]]);
        if ($validUser) {
            $user = Auth::getProvider()->retrieveByCredentials(['email' => $request["email"], 'password' => $request["password"]]);
            Auth::login($user);
            $token = $user->createToken($request["email"], ['server:update']);
            $user["api_token"] = $token->plainTextToken;
            $data = [
                'success' => true,
                'data' => new UserResource($user),
            ];
            return response($data)
                ->setStatusCode(200)->header('Status-Code', '200');

        } else {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => __('messages.invalid_user'),
            ], 401)->header('Status-Code', '401'));
        }

    }


    public function forgotPassword(Request $request)
    {
        try {
            $response = Password::sendResetLink(
                $request->only('email')
            );
            switch ($response) {
                case Password::RESET_LINK_SENT:
                    return response()->json(["message" => __("messages.reset_link")]);
                case Password::INVALID_USER:
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.reset_user_nf'),
                    ], )->header('Status-Code', 200);
            }
        } catch (Exception $e) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode())->header('Status-Code',  $e->getCode()));
        }

    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "errors" => $validator->errors()
            ],)->header('Status-Code', 200);
        }
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        return $status === Password::PASSWORD_RESET
            ? (response()->json(["message" =>__("messages.reset_success")]))
            : response()->json([
                'success' => false,
                'message' =>__('messages.unauthorized'),
            ], 401)->header('Status-Code', '401');
    }

    public function getCurrentUser()
    {
        $user = auth()->user();
        $user->avatar = isset($user->avatar) ? env("APP_URL") . "/" . $user->avatar : null;
        $data = ['success' => true, "data" => $user];
        return response()->json($data);
    }

    public function updateUserData(Request $request){
        $validated = Validator::make($request->all(), [
            'first_name' => 'min:5',
            'last_name' => 'min:5',
            'email' => 'email|min:5'
        ]);
        $errors = [];
        if(!empty($validated->errors()->messages())){
            foreach (array_keys($validated->errors()->toArray()) as $error){
                $errors[] = [$error => __('validation.'.$error)];
            }
            return response()->json(['messages' => $errors], 200);
        }
        $user = User::where('id', Auth::id())->first();
        $user->first_name = $request->first_name ?? $user->first_name;
        $user->last_name = $request->last_name ?? $user->last_name;
        $user->email = $request->email ?? $user->email;
        $user->avatar = $request->avatar ?? $user->avatar;
        $user->tax_identity_number = $request->tax_identity_number ?? $user->tax_identity_number;
        $user->company_name = $request->company_name ?? $user->company_name;
        if (!empty($request->current_password) && !empty($request->new_password)) {
            if(Hash::check($request->current_password, $user->password)){
                $user->password = Hash::make($request->new_password);
            }else{
                return response()->json([
                    'success' => false,
                    'message' =>__('messages.wrong_current_password'),
                ]);
            }
        }
        $user->save();
        return response()->json(['message' => __('messages.user-updated')]);
    }

}
