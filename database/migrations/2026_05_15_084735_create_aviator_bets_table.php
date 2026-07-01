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
        Schema::create('aviator_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aviator_round_id')->constrained('aviator_rounds')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('bet_amount', 18, 2);
            $table->decimal('win_amount', 18, 2)->default(0);
            $table->decimal('cashout_multiplier', 10, 2)->nullable();
            $table->enum('status', ['com', 'cashed_out', 'lost'])->default('pending');
            $table->decimal('after_amount', 18, 2)->default(0);
            $table->decimal('before_amount', 18,2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_bets');
    }
};
