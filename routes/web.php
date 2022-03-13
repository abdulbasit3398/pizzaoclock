<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

//Used for handling the html file of react project
View::addExtension('html', 'php');

Route::get('/{any}', function () {
    //path to dist folder index.html inside the views directory
    return view('build/index');
})->where('any', '.*');