<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-module', function() {
	return 'Hello from test-module';
})->name('test-module::index');
