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
    'middleware' => ['cors'], 'namespace' => 'Api'], function() {

    Route::get('/', function() {
        return ['status'=> 'ok', 'message' => 'api endpoint reached'];
    });

        Route::prefix('users')->group(function() {
            Route::get('{token}', 'UsersController@user');
            Route::get('avatars/{avatar}', 'UsersController@avatar');
            Route::post('create', 'UsersController@create');
            Route::post('login', 'UsersController@login');
            Route::post('fingerprint', 'UsersController@fingerprint');
            Route::post('logout', 'UsersController@logout');
            Route::post('new-avatar', 'UsersController@newAvatar');
        });
    }
);
