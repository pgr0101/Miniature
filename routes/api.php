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

Route::middleware(['miniatureauth'])->group(function () {

    Route::post('/sendcode' , 'Miniature@handleCode');

    Route::get('/getanswer/{username}/{password}/{id}' , 'Miniature@getTheAnswer');

    Route::get('/uploads/{username}/{password}' , 'Miniature@getAllUploads');

    Route::get('/getexecute/{username}/{password}/{id}' , 'Miniature@getExecution');

});

Route::get('/', function (Request $request) {
    return "Miniature is working well";
});


Route::post('/signup' , 'Miniature@signUp');


Route::post('/login' , 'Miniature@logIn');

