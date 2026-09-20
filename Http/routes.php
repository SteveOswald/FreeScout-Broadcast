<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => 'broadcast', 'namespace' => 'Modules\Broadcast\Http\Controllers'], function () {
    Route::get('/lists', ['uses' => 'BroadcastListsController@index'])->name('broadcast.lists');
    Route::get('/lists/create', ['uses' => 'BroadcastListsController@create'])->name('broadcast.lists.create');
    Route::post('/lists', ['uses' => 'BroadcastListsController@store'])->name('broadcast.lists.store');
    Route::get('/lists/{id}/edit', ['uses' => 'BroadcastListsController@edit'])->name('broadcast.lists.edit');
    Route::put('/lists/{id}', ['uses' => 'BroadcastListsController@update'])->name('broadcast.lists.update');
    Route::delete('/lists/{id}', ['uses' => 'BroadcastListsController@destroy'])->name('broadcast.lists.destroy');
});
