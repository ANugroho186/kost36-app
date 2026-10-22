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
        Schema::create('tenancy_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenancy_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->smallInteger('days')->nullable();
            $table->date('new_date')->nullable();
            $table->text('note')->nullable();
            // Keputusan tidak pernah diubah setelah dicatat, jadi tanpa updated_at.
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenancy_decisions');
    }
};
