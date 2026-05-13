<?php

use Illuminate\Support\Facades\Route;
use Modules\CvWriter\Http\Controllers\CvWriterController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cvwriters', CvWriterController::class)->names('cvwriter');
});
