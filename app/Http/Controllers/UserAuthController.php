<?php

namespace App\Http\Controllers;
use DB;
use App\User as User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ForgetRequest;
use App\Events\UserForgetPassword;

class UserAuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        if ($request->validator->fails()) {
            return response()->json(
                [
                    'result' => 0,
                    'error' => $request->validator->messages(),
                    'data' => [],
                ],
                400
            );
        }

        User::create(
            array_merge($request->all(), [
                'password' => Hash::make($request->password),
            ])
        )->save();

        return response()->json(
            [
                'result' => 1,
                'data' => ['report' => 'User Registered Successfully'],
            ],
            201
        );
    }

    public function login(LoginRequest $request)
    {
        if ($request->validator->fails()) {
            return response()->json(
                [
                    'result' => 0,
                    'error' => $request->validator->messages(),
                    'data' => [],
                ],
                200
            );
        }

        $credentials = $request->only(['email', 'password']);

        if (!($token = auth()->attempt($credentials))) {
            return response()->json(
                ['result' => 0, 'error' => 'INVALID credentials'],
                401
            );
        }

        return response()->json(
            [
                'result' => 1,
                'data' => ['token' => $token, 'user_id' => Auth::user()->id],
            ],
            200
        );
    }

    public function logout(Request $request)
    {
        auth()->logout();

        return response()->json(
            ['result' => 1, 'data' => ['message' => 'Successfully logged out']],
            200
        );
    }

    public function forget(ForgetRequest $request)
    {
        if ($request->validator->fails()) {
            return response()->json(
                [
                    'result' => 0,
                    'error' => $request->validator->messages(),
                    'data' => [],
                ],
                400
            );
        }

        $user = User::where('email', $request->email)->first();
        if (empty($user)) {
            return response()->json(
                [
                    'result' => 0,
                    'error' => [
                        'email' => ['User with this email does not exists.'],
                    ],
                    'data' => [],
                ],
                400
            );
        }

        //save token
        $token = md5(base64_encode(time()));
        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
        ]);

        event(
            new UserForgetPassword([
                'token' => $token,
                'email' => $user->email,
                'name' => $user->firstname . ' ' . $user->firstname,
            ])
        );
        return response()->json(
            [
                'result' => 1,
                'data' => [
                    'report' => 'Email Sent Successfully,Check you mail.',
                ],
            ],
            201
        );
    }
}
