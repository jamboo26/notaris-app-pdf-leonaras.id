<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // Mengizinkan semua kolom diisi data (Mass Assignment)
    protected $guarded = [];

    // Membuat ID Transaksi otomatis sebelum data disimpan
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id_transaksi = 'TRX-' . date('Ymd') . '-' . rand(1000, 9999);
        });
    }

    // Relasi ke tabel detail
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    // Relasi ke User (Manager yang menginput)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
