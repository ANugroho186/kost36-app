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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            // room_id kosong berarti area umum (dapur, kamar mandi bersama).
            $table->foreignId('room_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('complaint_id')->nullable()->constrained()->nullOnDelete();
            $table->date('performed_at');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
