 @foreach($recent_rounds as $result)
<div class="result-pill">
    <strong style="font-size:16px">#{{$result->round_id}}</strong>
    <span style="color:green;padding:5px 0;">{{$result->aviatorBets()->sum('bet_amount')}}</span>
    <span style="color:red;">{{$result->aviatorBets()->sum('win_amount')}}</span>
</div>
@endforeach