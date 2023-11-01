<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotasController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/notas', [NotasController::class, 'obterNotasDaAPI']);
Route::get('/agrupar-notas-por-remetente', [NotasController::class, 'agruparNotasPorRemetente']);
Route::get('/calcular-valores', [NotasController::class, 'calcularValores']);
