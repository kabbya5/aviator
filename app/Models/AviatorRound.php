<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviatorRound extends Model
{
    protected $fillable = [
        'round_id',
        'status',
        'crash_point',
        'total_bet_amount',
        'total_win_amount',
        'profit',
    ];

    public function aviatorBets()
    {
        return $this->hasMany(AviatorBet::class, 'aviator_round_id', 'round_id');
    }
}
