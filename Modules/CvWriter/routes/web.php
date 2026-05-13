<?php

use Illuminate\Support\Facades\Route;
use Modules\CvWriter\Http\Controllers\CvWriterController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cvwriters', CvWriterController::class)->names('cvwriter');
});
