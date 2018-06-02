<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/',  [
    'as' => 'home', 
    'uses' => 'viewController@home'
]);

Route::group(['prefix' => 'users'], function () {
	Route::get('/',  [
    	'as' => 'users', 
    	'uses' => 'viewController@view'
	]);
	Route::get('/add',  [
    	'as' => 'user_add', 
    	'uses' => 'viewController@user_add'
	]);
	Route::get('/log',  [
    	'as' => 'users_log', 
    	'uses' => 'viewController@log'
	]);			
});


// APPLICATION ROUTES
    Route::group(['prefix' => 'app'], function () {
        Route::post('/',  [
            'as' => 'app', 
            'uses' => 'appController@app'
        ]);                     
    });
        
    Route::get('/test',  [
            'as' => 'test', 
            'uses' => 'appController@test'
    ]); 

// Password reset link request routes...
Route::get('password/email', 'Auth\PasswordController@getEmail');
Route::post('password/email', 'Auth\PasswordController@postEmail');

// Password reset routes...
Route::get('password/reset/{token}', 'Auth\PasswordController@getReset');
Route::post('password/reset', 'Auth\PasswordController@postReset');

Route::any('/password/thankyou',  function () {
    return View::make('auth.thankyou');
}); 