@foreach($bots as $key => $bot)
    <div class="bet-list-item {{$round->crash_point >= $bot->cashout_point ? "cashout" : '' }}">
        <div class="item-column player">
            <img class="avatar" src="{{asset($bot->image)}}" alt="">
            <div class="username">{{substr($bot->name, 0, 1) . '***' . substr($bot->name, -1)}}</div>
        </div>
        <div class="item-column bet">
            <div class="ng-star-inserted"> {{$bot->bet_amount}} </div>
        </div>
        <div class="item-column x small"> {{ $round->crash_point >= $bot->cashout_point ? $bot->cashout_point : '' }} </div>
        <div class="item-column win"> {{ $round->crash_point >= $bot->cashout_point ? number_format($bot->cashout_point * $bot->bet_amount,2) : '' }} </div>
    </div>
@endforeach