<?php

use Encore\HasmanyExtra\Http\Controllers\HasManyMultipleImageController;
use Illuminate\Support\Facades\Route;

Route::post('hasmany-extra/delete', [HasManyMultipleImageController::class, 'delete']);
