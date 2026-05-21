<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AviatorRound;

class AviatorController extends Controller
{
    public function launchUrl()
    {
        $user_id = auth()->user()->id ?? 1;

        $user = User::find($user_id);

        $params = [
            'currency'     =>'USD', //$user->country->currency_code ?? ,
            'operator'     => 77435042,
            'jurisdiction' => 'CW',
            'lang'         => 'EN',
            'return_url'   => 'https://boomx.club',
            'user'         => $user_id,
            'token'        => md5(uniqid()),
        ];

        return redirect()->route('aviator.launch', $params);
    }

    public function launch(Request $request)
    {
        $currency = $request->input('currency');
        $images = [
            'data:image/jpeg;base64,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',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQaZnF8ElwCzCgHGTNVnaElToLnw3zE4AgEVQ&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQc-frmS69AAR02hdT_OO9JVhl4y_1H4ZzbUA&s',
        ];

        $rounds = AviatorRound::where('status','complete')->orderBy('id', 'desc')->limit(60)->get();
        return view('aviator.launch', compact('currency', 'images','rounds'));
    }

    public function generateRound()
    {
        $todate = date('Y-m-d');

        $round = AviatorRound::whereDate('created_at', $todate)
            ->orderBy('id', 'desc')
            ->first();

        $round_no = null;

        if (!$round) {

            $round_no = date('Ymd') . '00001';

        } else {

            $round_no = $round->round_id + 1;
        }

        $exist = AviatorRound::where('round_id', $round_no)->exists();

        while ($exist) {

            $round_no += 1;

            $exist = AviatorRound::where('round_id', $round_no)->exists();
        }

        AviatorRound::create([
            'round_id' => $round_no,
        ]);

        return response()->json([
            'round_no' => $round_no
        ]);
    }

    public function finishRound(Request $request)
    {
        $round_id = $request->round_id;

        $crash_point = $request->crash_point;

        $round = AviatorRound::where('round_id', $round_id)->first();

        if (!$round) {

            return response()->json([
                'message' => 'Round not Found !',
                'round_id' => $round_id,
            ], 404);
        }

        $totalBetAmount = $round->aviatorBets()->sum('bet_amount');

        $totalWinAmount = $round->aviatorBets()->sum('win_amount');

        $profit = $totalBetAmount - $totalWinAmount;

        $round->update([
            'status' => 'complete',
            'crash_point' => $crash_point,
            'total_bet_amount' => $totalBetAmount,
            'total_win_amount' => $totalWinAmount,
            'profit' => $profit
        ]);

        return response()->json([
            'success' => true,
            'round_id' => $round_id,
            'profit' => $profit
        ]);
    }

    public function crashPoint(){
        $rand = rand(1,4);

        $houseEdge = 0.99;

        // Generate secure random number
        $random = mt_rand() / mt_getrandmax();

        // Crash formula
        $multiplier = floor((100 * $houseEdge) / (1 - $random)) / 100;

        if($multiplier < 1){
            $multiplier = 2;
        }

        return response()->json(['crash_point' => $multiplier]);
    }
}
