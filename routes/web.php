<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'admin');

use App\Http\Controllers\MediaController;

Route::get('/media/{id}', [MediaController::class, 'show'])
	->name('media.show')
	->middleware('signed');