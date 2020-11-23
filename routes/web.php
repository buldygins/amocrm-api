<?php

use Illuminate\Support\Facades\Route;

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

Route::get('','HomeController@index')->name('index');

Route::get('/auth','HomeController@auth');

Route::get('/leads','HomeController@getLeads')->name('leads.index');

Route::get('/leads/create', 'HomeController@createLead')->name('leads.create');

Route::post('/leads/create','HomeController@storeLead')->name('leads.store');




