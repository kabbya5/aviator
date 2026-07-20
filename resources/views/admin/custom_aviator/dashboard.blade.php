@extends('layouts.aviator')

@section('title')
    Aviator | Dashboard
@endsection

@push('style')
<style>
.amount-wrapper{
    display:flex;
    gap:12px;
    width:100%;
}

.amount-card{
    width:100%;
    display:flex;
    flex-direction: column;
    justify-content:space-between;
    align-items:center;
    padding:12px 18px;
    border-radius:8px;
    color:#fff;
    font-weight:600;
    box-shadow:0 3px 10px rgba(0,0,0,.15);
}

.bet-card{
    background:linear-gradient(135deg,#28a745,#1e7e34);
}

.win-card{
    background:linear-gradient(135deg,#dc3545,#b02a37);
}

.amount-card .title{
    font-size:15px;
}

.amount-card .amount{
    font-size:20px;
    font-weight:700;
}
</style>
@endpush

@section('content')
    <h2 style="font-weight:600;font-size:20px;">Welcome back,
        BoomX    </h2>
    <p class="muted" style="margin-bottom:22px;">{{ date('F j, Y') }}  · Game performance summary</p>

    <section class="grid stats-cards">
        <div class="card-soft">
            <h3>Bet Volume</h3>
            <strong>৳ {{number_format($total_bet_amount,2)}}</strong>
            <div class="muted">{{number_format($total_transactions)}} transactions</div>
        </div>

        <div class="card-soft"><h3>Payouts</h3>
            <strong>৳{{number_format($total_bet_payout,2)}}</strong>
            <div class="muted">Wins + refunds</div>
        </div>

        <div class="card-soft">
            <h3>Operator Net</h3>
            <strong>৳{{number_format($house_earning,2)}}</strong>
            <div class="muted">House earnings</div>
        </div>
        <div class="card-soft">
            <h3>GGR</h3>
            @if($total_bet_payout && $total_bet_amount)
            <strong id="today_rtp"> {{round(($total_bet_payout / $total_bet_amount) * 100, 2)}}</strong>%
            @endif
            <div class="muted">Realized <span id="overall_rtp"> 95.00 </span>%</div>
        </div>
        
        <div class="card-soft">
            <h3>Unique Players</h3>
            <strong>{{$unique_players}}</strong>
        </div>
    </section>

    <section class="panel-wide" style="margin-top:30px;">
        <div class="panel-title" style="font-weight:600;">Recent Aviator Round</div>
        <div class="result-grid" style="margin-top:16px;" id="recent_rounds">
            @foreach($latest_results as $result)
            <div class="result-pill">
                <strong style="font-size:16px">#{{$result->round_id}}</strong>
                <span style="color:green;padding:5px 0;">{{$result->aviatorBets()->sum('bet_amount')}}</span>
                <span style="color:red;">{{$result->aviatorBets()->sum('win_amount')}}</span>
            </div>
            @endforeach
        </div>
    </section>
    
    <section class="panel-wide" style="margin-top:30px;">
        <div class="panel-title" style="font-weight:600;">Aviator Control</div>
        <div style="display:flex;margin-top:16px;width:100%;gap:4">
            <!-- Live Bets -->
            <div style="width:100%;padding:10px;border:1px solid #313131">
                <div class="d-flex">
                    <h3>Live Bets</h3>
                    <h3> Total users : <span id="active_users"> </span></h3>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>User Name </th>
                            <th> Country </th>
                            <th> Amount </th>
                            <th> Win Amount</th>
                        </tr>
                    </thead>
                    <tbody id="live_bet">
                        <tr>
                            <td colspan="6" class="muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="control-btn" style="width:450px !important;padding:0 10px;">
                <div style="display:flex; flex-direction:column;align-items:center;justify-content:center;padding:10px;border:1px solid #313131">
                    <div class="amount-wrapper">
                        <div class="amount-card bet-card">
                            <span class="title">Total Bets</span>
                            <span class="amount" id="total_bet_amount">0.00</span>
                        </div>

                        <div class="amount-card win-card">
                            <span class="title">Total Win</span>
                            <span class="amount" id="total_win_amount">0.00</span>
                        </div>
                    </div>

                    <div class="amount-wrapper" style="margin-top:10px">
                        <div class="amount-card bet-card">
                            <span class="title"> Crash  </span>
                            <span class="amount" id="crash_point">0.00</span>
                        </div>

                        <div class="amount-card win-card" id="crush" style="cursor: pointer;">
                            <span class="title"> Running </span>
                            <span class="amount" id="running">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
<script>
$(document).ready(function(){
    let round_id = null;
    let multiplier = 1.00;

    const socket = io("http://localhost:3000", {
        path: "/socket"
    });

    socket.on("connect", () => {
        socket.emit("join:room", {
            roomKey: "aviator",
            user_id: 'admin',
        });
    });

    socket.on("round:new", (data) => {
        round_id = data.roundId;
    });

    socket.on("multiplier:update", data => {
        multiplier = parseFloat(data.multiplier);
        $('#crash_point').text(data.crashPoint);
        $('#running').text(data.multiplier);
    });

    function loadBets(){
        $.ajax({
            url:'/aviator/live/bets',
            type:'get',
            data:{
                round_id:round_id,
            },
            success:function(res){
                if(res.total_bet_amount > 0){
                    $('#live_bet').html(res.html);
                }else{
                    $('#live_bet').html(`
                        <tr>
                            <td colspan="6" class="muted">Loading...</td>
                        </tr>
                    `);
                }
                $('#total_bet_amount').text(res.total_bet_amount);
                $('#total_win_amount').text(res.total_win_amount);
                $('#active_users').html(res.users);
                $('#recent_rounds').html(res.recent_rounds);
                $('#overall_rtp').html(res.rtp);
                $('#today_rtp').html(res.today_rtp)
            }
        })
    }

    setInterval(function(){
        loadBets();
    },2000);

    $(document).on('click', '#crush', function(){
        socket.emit('admin_crash', {
            crashPoint: 1
        });
    });
});
</script>

@endsection
