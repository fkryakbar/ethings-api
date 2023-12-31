<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StorageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'active_user']);

    Route::post('/storage/create-folder', [StorageController::class, 'create_folder']);
    Route::post('/storage/upload', [StorageController::class, 'file_upload']);
    Route::post('/storage/file-delete', [StorageController::class, 'file_delete']);
    Route::post('/storage/folder-delete', [StorageController::class, 'folder_delete']);
    Route::get('/storage/{folder_id}', [StorageController::class, 'get_item']);
    Route::post('/storage/update/{item_id}', [StorageController::class, 'update_item']);
});
Route::middleware('public.access')->group(function () {
    Route::post('/public/update/{item_id}', [PublicController::class, 'update_file']);
    Route::post('/public/file-delete', [PublicController::class, 'delete_file']);
    Route::post('/public/upload', [PublicController::class, 'upload']);
    Route::get('/public/view/{folder_id}', [PublicController::class, 'get_folder_items']);
});
Route::get('/storage/download/{item_id}', [StorageController::class, 'file_download']);
Route::get('/public/download/{item_id}', [PublicController::class, 'download_item']);

Route::get('/redirect', function (Request $request) {
    if ($request->has('u') && $request->has('item')) {
        setcookie('token_session', $request->u);
        return redirect('/api/storage/download/' . $request->item);
    }
    return back();
});
