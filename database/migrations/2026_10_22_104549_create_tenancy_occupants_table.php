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
        Schema::create('tenancy_occupants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nickname')->nullable();
            $table->date('moved_in_at');
            $table->date('moved_out_at')->nullable();
            $table->string('notify_channel')->nullable();
            $table->string('notify_contact')->nullable();
            $table->timestamps();
        });

        // Satu akun hanya boleh aktif di satu kamar. Penghuni tanpa akun
        // (user_id NULL) tidak terkena, karena setiap NULL dianggap berbeda.
        DB::statement(
            'CREATE UNIQUE INDEX tenancy_occupants_user_active_unique
                ON tenancy_occupants (user_id)
                WHERE moved_out_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenancy_occupants');
    }
};
