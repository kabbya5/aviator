<?php

namespace App\Http\Controllers;

use App\Models\AviatorBet;
use App\Models\AviatorBot;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AviatorRound;
use App\Models\TempAviatorBet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AviatorController extends Controller
{
    public function launchUrl()
    {
        $user_id = auth()->user()->id ?? 1;

        $user = User::find($user_id);

        $params = [
            'currency'     => 'USD', //$user->country->currency_code ?? 'USD',
            'operator'     => 77435042,
            'jurisdiction' => 'CW',
            'lang'         => 'EN',
            'return_url'   => 'https://boomx.club',
            'user'         => $user_id,
            'token'        => md5(uniqid()),
            'total_balance' => $user->total_balance,
        ];

        return redirect()->route('aviator.launch', $params);
    }

    public function launch(Request $request)
    {
        $currency = $request->input('currency');
        $rand = rand(1,8);
        $images = [
            asset("custom_aviator/img/av-{$rand}.png"),
            asset("custom_aviator/img/av-" . ($rand + 1) . ".png"),
            asset("custom_aviator/img/av-" . ($rand + 2) . ".png"),
        ];
        $bots = AviatorBot::where('status', 'active')->get();

        $rounds = AviatorRound::where('status','complete')->orderBy('id', 'desc')->limit(60)->get();
        return view('aviator.launch', compact('currency', 'images','rounds', 'bots'));
    }

    public function generateRound()
    {
        $today = now()->format('Y-m-d');

        $round = AviatorRound::whereDate('created_at', $today)
            ->orderByDesc('id')
            ->first();

        if ($round) {

            $round_no = $round->round_id;

            // Date part (YYYYMMDD)
            $prefix = substr($round_no, 0, 8);

            // Sequence part (last 6 digits)
            $last6 = substr($round_no, -6);

            $last6 = (int) $last6 + 1;

            if ($last6 > 999999) {
                $last6 = 1;
            }

            $round_no = $prefix . str_pad($last6, 6, '0', STR_PAD_LEFT);

        } else {

            // First round of the day
            $round_no = now()->format('Ymd') . '000001';
        }

        while (AviatorRound::where('round_id', $round_no)->exists()) {

            $prefix = substr($round_no, 0, 8);
            $last6 = (int) substr($round_no, -6) + 1;

            if ($last6 > 999999) {
                $last6 = 1;
            }

            $round_no = $prefix . str_pad($last6, 6, '0', STR_PAD_LEFT);
        }

        AviatorRound::create([
            'round_id' => $round_no,
            'status'  => 'betting',
        ]);

        $bots = AviatorBot::get();
        $old_bots = $bots;

        $change =  rand(1,5);

        $amounts = [
            1050,2080,5030,3090,4080,6080,7080,1500,1800,2500,3000,4000,700,4500,3500,10,15,20,
            6010, 9020, 50070, 1800, 1200, 6500,3500,3800,8020,5300,4700,2800,1020,2030,1040,500, 600, 700, 800, 900, 1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000,
            1000, 2000, 5000, 10000, 14000,8000,9000,1000,200, 7000, 8000, 9000, 10000, 12000, 13000, 14000, 15000, 16000, 17000, 18000, 19000,
            15000, 20000, 25000, 30000,10000, 50000, 100000, 200000, 300000, 400000, 500000, 600000, 700000, 800000, 900000,
        ];

        foreach($bots as $bot){
            $change =  rand(1,5);
            if($bot->bet_amount > 2000){
                $cashout = mt_rand(120, 570) / 100;
            }elseif($bot->bet_amount > 100){
                $cashout = mt_rand(100, 3070) / 100;
            }else{
                $cashout = mt_rand(900, 10000) / 100;
            }

            $bot->update([
                'bet_name' => $change === 1 ? Str::random(6) : $bot->bet_name,
                'bet_amount' => $change == 1 ? $amounts[array_rand($amounts)] : $bot->bet_amount,
                'cashout_point' => $cashout,
            ]);
        }

        return response()->json([
            'round_no' => $round_no,
            'bets' => $bots,
            'total_bets' => $bot->count() + rand(500,5000),
            'view' => view('aviator._tabs.previous_bets', compact('bots','round'))->render(),
            'crash_point' => $round->crash_point,
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
            'profit' => $profit,
        ]);

        $pending_bets = AviatorBet::where('status', '!=', 'complecte')->get();
        $pending_round = AviatorRound::where('status', '!=', 'complecte')->get();
        if($pending_bets->count() > 0){
            $pending_bets->each->update(['status' => 'complete']);
        }
        if($pending_round->count() > 0){
            $pending_round->each->update(['status' => 'complete']);
        }

        return response()->json([
            'success' => true,
            'round_id' => $round_id,
            'profit' => $profit
        ]);
    }

    public function crashPoint(Request $request){
        $round_id = $request->round_id;
        $round = AviatorRound::where('round_id', $round_id)->first();
    
        $temp_bets = TempAviatorBet::all();
        foreach($temp_bets as $bet){
            AviatorBet::create([
                'user_id' => $bet->user_id,
                'aviator_round_id' => $round->id,
                'bet_amount' => $bet->bet_amount,
                'auto_cashout' => $bet->auto_cashout,
                'auto_bet' => $bet->auto_bet ?? 0,
                'class_name' => $bet->class_name,
            ]);

            if(!$bet->auto_bet){
                $bet->delete();
            }
        }

        $bets = DB::table('aviator_bets')->where('status','complete')
                    ->select('bet_amount', 'win_amount')
                    ->orderByDesc('id')
                    ->limit(40000)
                    ->get();

        $houseEdge = 0.99;
        $random = mt_rand() / mt_getrandmax();

        $multiplier = floor((100 * $houseEdge) / (1 - $random)) / 100;

        $totalTempBet = $temp_bets->sum('bet_amount');
        $totalBet = $bets->sum('bet_amount') + $totalTempBet;
        $totalPayout = $bets->sum('win_amount');
        $houseEarning = $totalBet * 0.05;
        $allawablePayout =  ($totalBet - $houseEarning) - $totalPayout;
 
        if($temp_bets->count() > 0){
            $random = rand(1, 100);
            if ($random <= 15) {
                $multiplier = mt_rand(100, 580) / 100;
            } else {
                $faredMultiplier = $allawablePayout > 0 ? ($allawablePayout / $totalTempBet) : 1;
                $multiplier = min($multiplier, $faredMultiplier);
            }
        }

        // dd([
        //     'total_temp_bet' => $totalTempBet,
        //     'total_Bet' => $totalBet,
        //      'total_payout' => $totalPayout,
        //     'alloawble_payout' => $allawablePayout,
        //     'mult' => $multiplier,
        //     'random' => $random,
        //     'temp_bet' => $temp_bets,
        // ]);

        if($multiplier < 1){
            $multiplier = 1;
        }

       

        return response()->json(['crash_point' => $multiplier]);
    }

    public function placeBet(Request $request)
    {
        $round = AviatorRound::where('round_id', $request->round_id)->first();

        if (!$round) {
            return response()->json([
                'status' => 'error',
                'message' => 'Round not found'
            ], 404);
        }

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        if ($user->total_balance < $request->amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient balance'
            ], 400);
        }

        $isAuto = $request->autoBet;

        $exist = TempAviatorBet::query()->where([
            'aviator_round_id' => $round->id,
            'user_id' => $user->id,
            'class_name' => $request->className,
        ])->first();

        if ($exist) {
            return response()->json([
                'status' => 'success',
                'bet_status' => $round->status,
                'bet_id' => $exist->id ?? null,
                'class_name' => $exist->class_name,
            ]);
        }

        $bet = $this->betHelper([
            'round' => $round,
            'user' => $user,
            'amount' => $request->amount,
            'class_name' => $request->className,
            'auto_cashout' => $request->auto_cashout,
            'auto_bet' => $isAuto
        ], 'temp');

        // balance deduct
        $user->total_balance -= $request->amount;
        $user->save();

        return response()->json([
            'status' => 'success',
            'bet_status' => $round->status,
            'bet_id' => $bet->id ?? null,
            'class_name' => $bet->class_name,
        ]);
    }

    private function betHelper(array $data, $model = 'temp')
    {
        // decide model based on round status
        $bet = ($model === 'temp')
            ? new TempAviatorBet()
            : new AviatorBet();

        $bet->aviator_round_id = $data['round']->id;
        $bet->user_id = $data['user']->id;
        $bet->class_name = $data['class_name'];
        $bet->bet_amount = $data['amount'];
        $bet->auto_bet = $data['auto_bet'];
        $bet->auto_cashout = $data['auto_cashout'] ?? 0;

        $bet->save();

        return $bet;
    }

    public function cashout(Request $request){
        $round_id = $request->round_id;
        $user = User::find($request->user_id);
        
        $round = AviatorRound::where('round_id', $round_id)->first();

        $bet = AviatorBet::where('class_name',$request->class_name)->where('aviator_round_id', $round->id)->where('user_id',$request->user_id)->where('status','pending')->first();
        
        $bet->update([
            'win_amount' => $request->amount,
            'status' => 'complete',
            'after_amount' => $user->total_balance + $request->amount,
            'before_amount'  => $user->total_balance,
        ]);

        $user->increment('total_balance', $request->amount);

        $round = AviatorRound::with('aviatorBets')->where('round_id', $round_id)->first();
        $total_bet = $round->aviatorBets()->count();
        $complete_bet = $round->aviatorBets()->where('status', 'complete')->count();
        $updateCrushPoint = false;
        if($total_bet == $complete_bet){
            $updateCrushPoint = true;
        }

        return response()->json([
            'bet' => $bet,
            'updateCrushPoint' => $updateCrushPoint,
        ]);
    }

    public function checkBet(Request $request){
        $round_id = $request->round_id;
        $round = AviatorRound::where('round_id', $round_id)->first();
        $bets = TempAviatorBet::get();
        $running_bets = AviatorBet::where('aviator_round_id',$round->id)->where('status','pending')->get();

        return response()->json([
            'running_bets' => $running_bets,
            'bets' => $bets,
        ]);
    }

    public function cancelBet(Request $request){
        $user_id = $request->user_id;
        $class_name = $request->class_name;

        $bet = TempAviatorBet::where('class_name', $class_name)->where('user_id', $user_id)->first();
        if(!$bet){
            return response()->json([
                'status' => false,
                'message' => 'Bet not found!',
            ],400);
        }

        $bet->delete();

        return response()->json([
            'status' => true,
            'class_name' => $class_name,
        ]);
    }

    public function tabsData(Request $request){
        $type = $request->type;
        if($type == 'previous-bets'){
            $bots = AviatorBot::orderBy('bet_amount')->where('status', 'active')->get();
            $round = AviatorRound::where('status', 'complete')->orderBy('id','desc')->first();

            return response()->json([
                'view' => view('aviator._tabs.previous_bets', compact('bots','round'))->render(),
                'crash_point' => $round->crash_point,
            ]);
        }
    }

    public function deleteTempBet(Request $request){
        $user_id = $request->user_id;
        $temp = TempAviatorBet::where('user_id', $user_id)->get();
        if($temp->count() > 0){
            $temp->each->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Temp bets deleted successfully',
        ]);
    }
}
