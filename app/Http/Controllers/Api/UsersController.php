<?php
/**
 * Created by PhpStorm.
 * User: finleysiebert
 * Date: 25/09/2018
 * Time: 15:02
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Session;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    public function user($token) {

        $session = Session::where('token', $token)->first();

        if(!is_null($session) && $session->expires < new \DateTime()) {
            return new JsonResponse($session->user);
        } else {
            return new JsonResponse(['message' => 'invalid token'], 403);
        }

    }


    public function create(Request $request) {


        $request->validate([
            'username' => 'min:2|max:50|required|alpha_num|present|unique:users,name',
            'email' => 'email|present|required|unique:users,email',
            'password' => 'min:3|max:128|required|present'
        ]);

        User::create([
            'name' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password'))
        ]);

        return new JsonResponse(['message' => 'success']);

    }

    public function login(Request $request) {

        $request->validate([
            'username' => 'required|alpha_num|present',
            'password' => 'required|present',
            'uniqueId' => 'required|present'
        ]);

        $user = User::where('name', $request->input('username'))->first();

        if(Hash::check($request->input('password'), $user->password)) {

            $session = Session::create([
                'user_id' => $user->id,
                'expired' => false,
                'auth_method' => 'username+password',
                'unique_device_id' => $request->input('uniqueId'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'token' => bin2hex(openssl_random_pseudo_bytes(24)),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'expires' => new \DateTime('+120 days')
            ]);

            return ['message' => 'success', 'token' => $session->token];

        } else {
            return new JsonResponse(['message' => 'Password incorrect'], 422);
        }
    }

    public function fingerprint(Request $request) {

        $request->validate([
            'deviceId' => 'present|required'
        ]);

        $session = Session::where('unique_device_id', $request->input('deviceId'))->first();

        if(!is_null($session) && $session->expired = true) {
            if($session->user_agent == $_SERVER['HTTP_USER_AGENT']) {

                $newSession = Session::create([
                    'user_id' => $session->user->id,
                    'expired' => false,
                    'auth_method' => 'fingerprint',
                    'unique_device_id' => $request->input('deviceId'),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'token' => bin2hex(openssl_random_pseudo_bytes(24)),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'expires' => new \DateTime('+120 days')
                ]);

                return ['message' => 'success', 'token' => $newSession->token];

            } else {
                return new JsonResponse(['message' => 'too_dangerous'], 403);
            }
        } else {
            return new JsonResponse(['message' => 'regular_login_first'], 404);
        }
    }

    public function logout(Request $request) {

        $request->validate([
            'deviceId' => 'required|present',
            'token' => 'required|present'
        ]);

        $session = Session::where(['unique_device_id' => $request->input('deviceId'), 'token' => $request->input('token')])->first();

        if(!is_null($session)) {
            $session->expired = true;
            $session->save();
        }

        return ['message' => 'logout_ok'];

    }

    public function avatar($avatar) {

        return response(
            Storage::get('public/avatars/' . $avatar)
        )->header('Content-type', 'image/png');

    }

    public function newAvatar(Request $request) {

        $request->validate([
            'image' => 'present|required',
            'id' => 'present|required|integer'
        ]);

        $avatar_name = bin2hex(openssl_random_pseudo_bytes(16)) . '.png';
        $user = User::find($request->input('id'));

        $user->avatar = $avatar_name;

        $user->save();
        Storage::put('public/avatars/' . $avatar_name, base64_decode($request->input('image')));

        return ['ok'];

    }
}