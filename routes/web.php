<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\SendReportDailyController;

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

Route::get('/', function () {
    //return view('welcome');
    abort(404, 'Tidak ditemukan');
});

//Route::get('/testmail', [SendReportDailyController::class,'index']);
//Route::get('/testquery', [SendReportDailyController::class,'test']);
