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
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            // Data paket ikut terhapus saat akun penghuni dihapus.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_number');
            // Dienkripsi lewat cast model. Hasil enkripsi lebih panjang dari teks asli, jadi pakai text.
            $table->text('item_name')->nullable();
            $table->date('expected_date')->nullable();
            $table->timestamp('held_by_owner_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();

            // Sengaja tidak unik global: penolakan resi milik penghuni lain akan membocorkan datanya.
            $table->unique(['user_id', 'tracking_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
