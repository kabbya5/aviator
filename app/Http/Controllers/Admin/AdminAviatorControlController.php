<?php

namespace App\Http\Controllers\Admin;
use App\Models\AviatorRound;
use App\Models\User;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AviatorBet;

class AdminAviatorControlController extends Controller
{
    public function aviatorDashboard(){
        $today = date('Y-m-d');
        $lastMonth = date('Y-m-d', strtotime('first day of last month'));
        $total_bets = DB::table('aviator_bets')->whereDate('created_at', $today)->get();
      

        $total_bet_amount = $total_bets->sum('bet_amount');
        $total_bet_payout = $total_bets->sum('win_amount');
        $house_earning = $total_bet_amount - $total_bet_payout;

        $unique_players = DB::table('aviator_bets as today_bets')
            ->whereDate('today_bets.created_at', $today)
            ->whereNotNull('today_bets.user_id')
            ->whereExists(function ($query) use ($today, $lastMonth) {
                $query->select(DB::raw(1))
                    ->from('aviator_bets as old_bets')
                    ->whereColumn('old_bets.user_id', 'today_bets.user_id')
                    ->whereDate('old_bets.created_at', '>=', $lastMonth)
                    ->whereDate('old_bets.created_at', '<', $today);
            })
            ->distinct('today_bets.user_id')
            ->count('today_bets.user_id');

       
        $total_transactions = $total_bets->count();

        $latest_results = AviatorRound::with('aviatorBets')->orderBy('id', 'desc')->take(7)->get();

        return view('admin.custom_aviator.dashboard', compact(
            'total_bet_amount', 'total_bet_payout', 'total_transactions',
            'house_earning', 'unique_players','latest_results'
        ));

    }

    public function liveBets(Request $request){
        $round_id = $request->round_id;
        $bets = DB::table('aviator_bets')->where('status','complete')
                    ->select('bet_amount', 'win_amount')
                    ->orderByDesc('id')
                    ->limit(40000)
                    ->get();

        $today = date('Y-m-d');
        $lastMonth = date('Y-m-d', strtotime('first day of last month'));
        $total_bets = DB::table('aviator_bets')->whereDate('created_at', $today)->get();

        $today_bet_amount = $total_bets->sum('bet_amount');
        $today_win_amount = $total_bets->sum('win_amount');
        $today_rtp = 0;
        if($today_bet_amount && $today_win_amount){
            $today_rtp = ($today_win_amount / $today_bet_amount) * 100;
        }

        $round = AviatorRound::with('aviatorBets')->where('round_id',$round_id)->first();
        
        $users = $round->aviatorBets()->distinct('user_id')->count();
        $recent_rounds = AviatorRound::with('aviatorBets')->where('status','complete')->orderBy('id','desc')->limit(7)->get();

        $total_bet_amount = $bets->sum('bet_amount');
        $total_win_amount = $bets->sum('win_amount');

        $rtp = ($total_win_amount / $total_bet_amount) * 100;

        return response()->json([
            'html' => view('admin.custom_aviator._live_bets', compact('round'))->render(),
            'total_bet_amount' => $round->aviatorBets()->sum('bet_amount'),
            'total_win_amount' => $round->aviatorBets()->sum('win_amount'),
            'users' => $users,
            'recent_rounds' => view('admin.custom_aviator._recent_rounds',compact('recent_rounds'))->render(),
            'rtp' => number_format($rtp, 2),
            'today_rtp' => number_format($today_rtp,2),
        ]);
    }

    public function transactions(Request $request)
    {
        $limit = $request->items ?? 50;

        $query = AviatorBet::latest('id');

        // Filter by User ID
        $query->when($request->user_name, function ($q) use ($request) {
            $q->where('user_name', $request->user_name);
        });
        
        $query->when($request->country_id, function ($q) use ($request) {
            $q->where('coutry_id', $request->country_id);
        });

        // Filter by User Name
        $query->when($request->round_id, function ($q) use ($request) {
            $round_ids = AviatorRound::where('round_id', 'like', "%{$request->round_id}%")
                ->pluck('id');

            $q->whereIn('round_id', $round_ids);
        });

        // Filter by Type
        $query->when($request->type && $request->type !== 'all', function ($q) use ($request) {
            if ($request->type === 'bet') {
                $q->where('win_amount', 0);
            }

            if ($request->type === 'win') {
                $q->where('win_amount', '>', 0);
            }
        });

        // Date Range Filter
        $query->when($request->from, function ($q) use ($request) {
            $q->whereDate('created_at', '>=', $request->from);
        });

        $query->when($request->to, function ($q) use ($request) {
            $q->whereDate('created_at', '<=', $request->to);
        });

        $game_histories = $query->paginate($limit)->withQueryString();
        $countries = Country::where('status',1)->get();

        return view('admin.aviator_wingo.transactions', compact('game_histories','countries'));
    }
}
