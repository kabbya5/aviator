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
        Schema::create('aviator_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('round_id')->unique();
            $table->enum('status', ['pending', 'complete'])->default('pending');
            $table->decimal('crash_point', 10, 2)->nullable();
            $table->decimal('total_bet_amount',18,2)->default(0);
            $table->decimal('total_win_amount',18,2)->default(0);
            $table->decimal('profit',18,2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_rounds');
    }
};
