<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('paid_until');
            $table->date('extended_until')->nullable();
            $table->string('status');
            // "Bersedia berbagi kamar": orang kedua hanya bisa ditambahkan kalau true.
            $table->boolean('accepts_roommate')->default(false);
            $table->timestamp('deactivated_at')->nullable();
            $table->date('vacate_by')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // Satu kamar maksimal satu sewa yang menempati. Schema builder belum
        // mendukung partial index, jadi ditulis dengan raw SQL.
        DB::statement(
            "CREATE UNIQUE INDEX tenancies_room_occupying_unique
                ON tenancies (room_id)
                WHERE status IN ('active', 'inactive')"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenancies');
    }
};
