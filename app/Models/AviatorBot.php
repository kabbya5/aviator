<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviatorBot extends Model
{
    protected $fillable = [
        'bet_amount','cashout_point', 'bet_name',
    ];
}
