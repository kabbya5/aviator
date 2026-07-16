<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviatorBet extends Model
{
    protected $fillable = [
        'win_amount', 'bet_amount', 'status', 'after_amount',
        'before_amount','user_id','aviator_round_id', 'class_name',
        'auto_bet', 'auto_cashout',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
