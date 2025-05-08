<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    //return json response(['message' => 'Hello World']);

    return [
        'message' => 'Hello World',
        'status' => 200,
    ];


});
