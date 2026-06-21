<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\KanbanListController;
use App\Http\Controllers\CardController;

Route::apiResource('boards', BoardController::class);
Route::apiResource('kanban-lists', KanbanListController::class);
Route::apiResource('cards', CardController::class);
