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

Route::get('/', function () {
    return view('welcome');
});

//Auth::routes();
//Route::get('/home', 'HomeController@index')->name('home');

Route::get('/account_login','WalletController@account_login');

Route::post('/account','WalletController@access_account');

Route::get('/new_wallet','WalletController@create_new_wallet');
Route::post('/created_wallet','WalletController@wallet_created');

Route::get('/lookupTX','WalletController@lookup_transaction');
Route::post('/lookupTX','WalletController@get_transactionInfo');

Route::post('/send_coins','WalletController@send_coins');