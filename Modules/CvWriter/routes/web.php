<?php

use Illuminate\Support\Facades\Route;
use Modules\CvWriter\Http\Controllers\CvWriterController;
use Modules\CvWriter\Http\Controllers\KnowledgeFileController;

Route::middleware(['auth', 'verified'])->prefix('cv-writer')->name('cvwriter.')->group(function () {
    // Main CV generation UI
    Route::get('/', [CvWriterController::class, 'index'])->name('index');
    Route::post('/generate', [CvWriterController::class, 'generate'])->name('generate');
    Route::get('/session/{uuid}', [CvWriterController::class, 'session'])->name('session');
    Route::post('/session/{uuid}/answer', [CvWriterController::class, 'answer'])->name('answer');
    Route::get('/session/{uuid}/pdf', [CvWriterController::class, 'downloadPdf'])->name('pdf');

    // Knowledge file management
    Route::prefix('knowledge')->name('knowledge.')->group(function () {
        Route::get('/', [KnowledgeFileController::class, 'index'])->name('index');
        Route::get('/create', [KnowledgeFileController::class, 'create'])->name('create');
        Route::post('/', [KnowledgeFileController::class, 'store'])->name('store');
        Route::get('/{knowledgeFile}/edit', [KnowledgeFileController::class, 'edit'])->name('edit');
        Route::put('/{knowledgeFile}', [KnowledgeFileController::class, 'update'])->name('update');
        Route::delete('/{knowledgeFile}', [KnowledgeFileController::class, 'destroy'])->name('destroy');
        Route::post('/{knowledgeFile}/ingest', [KnowledgeFileController::class, 'reIngest'])->name('ingest');
    });
});
