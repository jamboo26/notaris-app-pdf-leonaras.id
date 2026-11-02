<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->string('id_transaksi')->unique();
        $table->string('nama_lengkap');
        $table->date('tanggal_permohonan');
        $table->enum('kategori', ['Bank', 'Perorangan', 'Badan Hukum']);
        $table->string('nama_bank')->nullable();
        $table->decimal('plafon', 15, 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
