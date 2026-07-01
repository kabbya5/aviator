<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviatorBet extends Model
{
    protected $fillable = [
        'win_amount', 'bet_amount', 'status', 'after_balance',
        'before_balance','user_id','aviator_round_id', 'class_name',
        'auto_bet', 'auto_cashout',
    ];
}
