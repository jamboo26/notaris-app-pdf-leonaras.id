<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    // Jika user adalah Manager atau Admin, arahkan ke Daftar Transaksi
    if ($user->hasAnyRole(['Manager', 'Admin'])) {
        return redirect()->route('trx.index');
    }

    // Jika user adalah Notaris, arahkan langsung ke Laporan Keuangan
    if ($user->hasRole('Notaris')) {
        return redirect()->route('laporan.keuangan');
    }

    // Fallback jika role belum diatur
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rute khusus sistem Notaris
Route::middleware('auth')->group(function () {

// Khusus Manager & Admin (Input, Edit, Update Status)
    Route::middleware(['role:Manager|Admin'])->group(function () {
        Route::get('/transaksi/input-1', [TransactionController::class, 'createStep1'])->name('trx.step1');
        Route::post('/transaksi/input-1', [TransactionController::class, 'storeStep1'])->name('trx.store1');

        Route::get('/transaksi/input-2', [TransactionController::class, 'createStep2'])->name('trx.step2');
        Route::post('/transaksi/input-2', [TransactionController::class, 'storeStep2'])->name('trx.store2');

        Route::get('/transaksi', [TransactionController::class, 'index'])->name('trx.index');
        Route::get('/transaksi/detail/{id}', [TransactionController::class, 'show'])->name('trx.show');
        Route::patch('/transaksi/detail/{id}/status', [TransactionController::class, 'updateStatus'])->name('trx.updateStatus');

        // (Rute delete sudah dihapus dari sini)
    });

    // TAMBAHAN BARU: Khusus HANYA Admin (Delete)
    Route::middleware(['role:Admin'])->group(function () {
        Route::delete('/transaksi/{id}', [TransactionController::class, 'destroy'])->name('trx.destroy');
    });

    // Khusus HANYA Admin (Delete Transaksi & Manajemen User)
    Route::middleware(['role:Admin'])->group(function () {
        Route::delete('/transaksi/{id}', [TransactionController::class, 'destroy'])->name('trx.destroy');

        // TAMBAHAN BARU: Rute Kelola User
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'destroy']);
    });

    // Khusus Notaris & Admin (Laporan Keuangan)
    Route::middleware(['role:Notaris|Admin'])->group(function () {
        Route::get('/laporan/keuangan', [TransactionController::class, 'keuangan'])->name('laporan.keuangan');

        // TAMBAHAN BARU: Rute Cetak PDF
        Route::get('/laporan/keuangan/pdf', [TransactionController::class, 'cetakPdf'])->name('laporan.pdf');
    });

    // Bawaan Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
