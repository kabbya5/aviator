@foreach($round->aviatorBets as $bet)
<tr>
    <td> {{$bet->user->user_name ?? ''}} </td>
    <td> {{$bet->user->first_name .' '. $bet->user->last_name}} </td>
    {{-- <td> {{$bet->user->country->country_name}} </td> --}}
    <td style="color: green"> {{$bet->bet_amount}}</td>
    <td style="color:red">{{$bet->win_amount}}</td>
</tr>
@endforeach