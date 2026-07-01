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
        Schema::create('aviatory_bots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('bet_amount',12,2);
            $table->decimal('cashout_point',10,2);
            $table->decimal('max_round',12,2);
            $table->string('image');
            $table->decimal('win',12,2);
            $table->decimal('monthly_bet_amount',12,2);
            $table->decimal('monthly_win',12,2);
            $table->decimal('monthly_max_round',12,2);
            $table->decimal('yearly_bet_amount');
            $table->decimal('yearly_max_win',12,2);
            $table->decimal('yearly_max_round',12,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviatory_bots');
    }
};
