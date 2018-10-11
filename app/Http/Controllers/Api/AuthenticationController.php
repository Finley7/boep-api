<?php
/**
 * Created by PhpStorm.
 * User: finleysiebert
 * Date: 09/10/2018
 * Time: 14:15
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Session;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    public function create(Request $request) {


        $request->validate([
            'username' => 'min:2|max:50|required|alpha_num|present|unique:users,name',
            'email' => 'email|present|required|unique:users,email',
            'password' => 'min:3|max:128|required|present'
        ]);

        $user = new User();

        $user->name = $request->input('username');
        $user->email = $request->input('email');
        $user->password =  Hash::make($request->input('password'));
        $user->firebaseId = "TBD";

        $user->save();

        return new JsonResponse(['message' => 'success', 'user' => $user]);

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

            return ['message' => 'success', 'token' => $session->token, 'user' => $session->user];

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

    public function setFbId(Request $request) {

        $request->validate([
            'firebaseId' => 'present|required',
            'user_id' => 'present|required|integer'
        ]);

        $user = User::find($request->input('user_id'));

        $user->firebaseId = $request->input('firebaseId');
        $user->save();

        return new JsonResponse(['success']);

    }
}