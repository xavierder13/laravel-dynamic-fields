<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Route
Route::prefix('auth')->group(function(){
    Route::get('/init', [
        'uses' => 'API\AuthController@init',
        'as' => 'auth.init'
    ])->middleware('auth:api');

    Route::post('/login', [
        'uses' => 'API\AuthController@login',
        'as' => 'auth.login'
    ]);

    Route::post('/register', [
        'uses' => 'API/AuthController@register',
        'as' => 'auth.register'
    ]);

    Route::get('/logout', [
        'uses' => 'API\AuthController@logout',
        'as' => 'auth.logout'
    ])->middleware('auth:api');
});

// User Routes
Route::group(['prefix' => 'user', 'middleware' => ['auth:api', 'user.maintenance']], function(){
    Route::get('/index', [
        'uses' => 'API\UserController@index',
        'as' => 'user.index',
    ]);

    Route::get('/create', [
        'uses' => 'API\UserController@create',
        'as' => 'user.create',
    ]);

    Route::post('/store', [
        'uses' => 'API\UserController@store',
        'as' => 'user.store',
    ]);

    Route::get('/edit/{id}', [
        'uses' => 'API\UserController@edit',
        'as' => 'user.edit',
    ]);

    Route::post('/update/{id}', [
        'uses' => 'API\UserController@update',
        'as' => 'user.update',
    ]);

    Route::post('/update_profile/{id}', [
        'uses' => 'API\UserController@update_profile',
        'as' => 'user.update_profile',
    ]);

    Route::post('/delete', [
        'uses' => 'API\UserController@delete',
        'as' => 'user.delete',
    ]);

    Route::get('/roles_permissions', [
        'uses' => 'API\UserController@userRolesPermissions',
        'as' => 'user.roles_permissions',
    ]);

});

//Permissions
Route::group(['prefix' => 'sap', 'middleware' => ['auth:api']], function(){

    Route::get('/ar_invoice', [
        'uses' => 'API\SAPModuleController@get_ar_invoice_fields',
        'as' => 'get.ar.invoice.fields',
    ]);

    Route::group(['prefix' => 'udf'], function(){
        Route::get('/index', [
            'uses' => 'API\SAPUDFController@index',
            'as' => 'sap.udf.index',
        ]);
        Route::get('/create', [
            'uses' => 'API\SAPUDFController@create',
            'as' => 'sap.udf.create',
        ]);
        Route::post('/store', [
            'uses' => 'API\SAPUDFController@store',
            'as' => 'sap.udf.store',
        ]);
        Route::post('/store_field', [
            'uses' => 'API\SAPUDFController@store_field',
            'as' => 'sap.udf.store.field',
        ]);
        Route::post('/store_option', [
            'uses' => 'API\SAPUDFController@store_option',
            'as' => 'sap.udf.store.option',
        ]);
        Route::post('/edit', [
            'uses' => 'API\SAPUDFController@edit',
            'as' => 'sap.udf.edit',
        ]);
        Route::post('/update/{id}', [
            'uses' => 'API\SAPUDFController@update',
            'as' => 'sap.udf.update',
        ]);
        Route::post('/update_field/{id}', [
            'uses' => 'API\SAPUDFController@update_field',
            'as' => 'sap.udf.update.field',
        ]);
        Route::post('/update_option/{id}', [
            'uses' => 'API\SAPUDFController@update_option',
            'as' => 'sap.udf.update.option',
        ]);
        Route::post('/delete', [
            'uses' => 'API\SAPUDFController@delete',
            'as' => 'sap.udf.delete',
        ]);
        Route::post('/delete_field', [
            'uses' => 'API\SAPUDFController@delete_field',
            'as' => 'sap.udf.delete.field',
        ]);
        Route::post('/delete_option', [
            'uses' => 'API\SAPUDFController@delete_option',
            'as' => 'sap.udf.delete.option',
        ]);
    });
    
    

});

//Permissions
Route::group(['prefix' => 'permission', 'middleware' => ['auth:api', 'permission.maintenance']], function(){
    Route::get('/index', [
        'uses' => 'API\PermissionController@index',
        'as' => 'permission.index',
    ]);
    Route::get('/create', [
        'uses' => 'API\PermissionController@create',
        'as' => 'permission.create',
    ]);
    Route::post('/store', [
        'uses' => 'API\PermissionController@store',
        'as' => 'permission.store',
    ]);
    Route::post('/edit', [
        'uses' => 'API\PermissionController@edit',
        'as' => 'permission.edit',
    ]);
    Route::post('/update/{id}', [
        'uses' => 'API\PermissionController@update',
        'as' => 'permission.update',
    ]);
    Route::post('/delete', [
        'uses' => 'API\PermissionController@delete',
        'as' => 'permission.delete',
    ]);

});

//Roles
Route::group(['prefix' => 'role', 'middleware' => ['auth:api', 'role.maintenance']], function(){
    Route::get('/index', [
        'uses' => 'API\RoleController@index',
        'as' => 'role.index',
    ]);
    Route::get('/create', [
        'uses' => 'API\RoleController@create',
        'as' => 'role.create',
    ]);
    Route::post('/store', [
        'uses' => 'API\RoleController@store',
        'as' => 'role.store',
    ]);
    Route::post('/edit', [
        'uses' => 'API\RoleController@edit',
        'as' => 'role.edit',
    ]);
    Route::post('/update/{id}', [
        'uses' => 'API\RoleController@update',
        'as' => 'role.update',
    ]);
    Route::post('/delete', [
        'uses' => 'API\RoleController@delete',
        'as' => 'role.delete',
    ]);

});

//Activity Logs
Route::group(['prefix' => 'activity_logs', 'middleware' => ['auth:api', 'activity.logs']], function(){
    Route::get('/index', [
        'uses' => 'API\ActivityLogController@activity_logs',
        'as' => 'activity_logs.index',
    ]);
    
});

Route::get('sap', 'API\UserController@sap');