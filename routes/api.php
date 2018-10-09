<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'namespace' => 'Api'], function() {

    Route::get('/', function() {
        return ['status'=> 'ok', 'message' => 'api endpoint reached'];
    });

        Route::prefix('users')->group(function() {
            Route::get('{token}', 'UsersController@user');
            Route::get('avatars/{avatar}', 'UsersController@avatar');
            Route::post('create', 'AuthenticationController@create');
            Route::post('login', 'AuthenticationController@login');
            Route::post('fingerprint', 'AuthenticationController@fingerprint');
            Route::post('logout', 'UsersController@logout');
            Route::post('new-avatar', 'UsersController@newAvatar');
            Route::get('search/{token}/{username}', 'UsersController@searchUsers');
        });
    }
);
