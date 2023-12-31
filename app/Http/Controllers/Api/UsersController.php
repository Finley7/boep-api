<?php
/**
 * Created by PhpStorm.
 * User: finleysiebert
 * Date: 25/09/2018
 * Time: 15:02
 */

namespace App\Http\Controllers\Api;


use App\buddy;
use App\Http\Controllers\Controller;
use App\Session;
use App\User;
use http\Exception\InvalidArgumentException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\UnauthorizedException;
use Image;

class UsersController extends Controller
{
    public function user($token) {

        $session = Session::where('token', $token)->first();

        if(!is_null($session) && $session->expires < new \DateTime()) {

            $user = $session->user;
            $user['buddies'] = Buddy::where(['first_user' => $session->user->id, 'second_user' => $session->user->id, 'status' => 'accepted']);

            return new JsonResponse($user);
        } else {
            return new JsonResponse(['message' => 'invalid token'], 403);
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

        $img = Image::make(Storage::get('public/avatars/' . $avatar))->resize(200,200);
        return $img->response('png');

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

        return ['status' => 'ok', 'avatar' => $avatar_name];

    }

    public function searchUsers($token, $username) {

        $sessionCheck = Session::where(['token' => $token, 'expired' => false])->first();

        if(!is_null($sessionCheck)) {

            $users = User::where('name', 'like', '%' . $username . '%')->select(['id', 'name', 'avatar']);

            return new JsonResponse([
                'status' => 'success',
                'amount' => $users->count(),
                'results' => $users->get()->toArray()
            ]);

        } else {
            throw new UnauthorizedException();
        }
    }

    public function addBuddy(Request $request) {

        $buddyCheck = Buddy::where(['first_user' => $request->input('first_user'), 'second_user' => $request->input('second_user')]);

        if($buddyCheck->count() > 0) {
            return new JsonResponse([
                'request' => 'not_sent',
                'message' => 'This request has already been sent'
            ], 400);
        }

        $buddy = new Buddy();

        $buddy->first_user = $request->input('first_user');
        $buddy->second_user = $request->input('second_user');
        $buddy->status = "pending";

        $buddy->save();

        return new JsonResponse([
            'request' => 'sent',
            'firebaseId' => User::find($buddy->second_user)->firebaseId
        ]);

    }
}