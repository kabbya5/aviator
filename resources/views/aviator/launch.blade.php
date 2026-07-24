<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>BoomX</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <link rel="stylesheet" href="{{ asset('custom_aviator/css/main.css') }}">
</head>

<body id="aviator">
    <input type="text" value="{{$rounds->last()->round_id ?? 1}}" id="round_id" style="display:none">
    <input id="user_id" style="display:none" value="1">

    <div class="bet-notify-box">
        <div class="cashout">
            <div class="title">You have cashed out!</div>
            <div class="multi">115x</div>
        </div>
        <div class="win-box">
            <div class="win-title">Win {{$currency}}</div>
            <div class="win-amount">115.00</div>
        </div>
        <div class="close-btn">&times;</div>
    </div>

    <div id="main-loading">
        <div class="loading">
            <div class="main-loading-powered-by">
                <div class="powered-by">
                    <img src="{{ asset('custom_aviator/img/power_by.png') }}" alt="">
                </div>
            </div>
            <div class="main-loading-logo">
                <img src="{{ asset('custom_aviator/img/welcome_logo.png') }}" alt="">
            </div>
            <div class="spinner">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </div>

    <div class="main-section">
        <div class="top-bar">
            <img src="{{asset('custom_aviator/img/aviator-logo.svg')}}" alt="" class="logo-img">
            <div class="right-side">
                <div class="blance-amount">
                    <span class="balance-amount-value" id="total-balance"> 600.43 </span>
                    <span class="balance-amount-text"> {{$currency}} </span>
                </div>
                <div class="bars">
                    <img src="{{asset('custom_aviator/img/burger.svg')}}" alt="">
                </div>
            </div>
        </div>

        <div class="game-container">
            <div class="bet-details">
                <div class="navigation-switcher">
                    <button class="bet-tab tab active" data-target=".live-all-bets"> All Bets </button>
                    <button class="bet-tab tab" data-target=".previous-bets"> Previous </button>
                    <button class="bet-tab tab" data-target=".top-history"> Top </button>
                </div>

                <div class="tab-details live-all-bets active">
                    <div class="bet-wins">
                        <div class="header">
                            <div class="avatars">
                                @foreach($images as $key => $image)
                                    <img src="{{$image}}" class="player img_{{$key}}" alt="">
                                @endforeach
                            </div>

                            <span class="win-amount" id="total-win"> {{number_format(rand(10000,20000),2)}} </span>
                        </div>
                        <div class="stats">
                            <div class="bets">
                                @php
                                    $total_bets_count = rand(300,2000);
                                @endphp
                                <span class="bets-count"> <span id="payout-bet" data-total_bets="{{$total_bets_count}}"> 0 </span>/<span id="total_bet">{{$total_bets_count}}</span> </span>
                                <span class="bets-label"> Bets</span>
                            </div>
                            <div class="total-win"> Total win {{$currency}} </div>
                        </div>

                        <div class="progress">
                            <div class="progress-bar" style="width:0%;"></div>
                        </div>
                    </div>

                    <div class="live-bet">
                        <div class="header">
                            <span class="header-item player">Player</span>
                            <span  class="header-item bet"> Bet USD </span>
                            <span class="header-item x">X</span>
                            <span class="header-item win"> Win USD </span>
                        </div>

                        <div class="bet-list">
                            <div class="bet-items runninge-bet-items">
                                @foreach($bots as $key => $bot)
                                    <div class="bet-list-item bet-autocashout" data-bet_amount="{{$bot->bet_amount}}" data-cashout="{{$bot->cashout_point}}">
                                        <div class="item-column player">
                                            <img class="avatar" src="{{asset($bot->image)}}" alt="">
                                            <div class="username">{{substr($bot->name, 0, 1) . '***' . substr($bot->name, -1)}}</div>
                                        </div>
                                        <div class="item-column bet">
                                            <div  class="ng-star-inserted"> {{$bot->bet_amount}} </div>
                                        </div>
                                        <div class="item-column x"> </div>
                                        <div class="item-column win"> </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-details previous-bets">
                    <div class="ng-star-inserted">
                        <div class="previous-round-result">
                            <div class="result-text">
                                Round Result
                            </div>
                            <div class="result-multiplier">
                                11.78x
                            </div>
                        </div>
                    </div>
                    <div class="live-bet">
                        <div class="header">
                            <span class="header-item player">Player</span>
                            <span  class="header-item bet"> Bet {{$currency}} </span>
                            <span class="header-item x">X</span>
                            <span class="header-item win"> Win </span>
                        </div>

                        <div class="bet-list">
                            <div class="bet-items">
                            
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-details top-history">
                    <div class="d-flex h-100 flex-column">
                        <div class="top-tab-switcher">
                            <div class="top-tab-switcher-row">
                                <button class="swicher-btn active"> X </button>
                                <button class="swicher-btn"> Win </button>
                                <button class="swicher-btn"> Rounds </button>
                            </div>
                            <div class="top-tab-switcher-row">
                                <button class="swicher-btn active"> Day </button>
                                <button class="swicher-btn"> Month </button>
                                <button class="swicher-btn"> Year </button>
                            </div>
                        </div>
                        <div class="top-list h-100 ng-star-inserted">
                            <div class="h-100 scroll-hide top-wins-list">
                                <div class="all active">
                                    <div class="ng-star-inserted">
                                        @for($i = 0; $i < 200; $i++)
                                        <div class="top-wins-list-item">
                                            <div class="top-wins-list-item-row">
                                                <img alt="avatar" class="avatar" src="{{$images[0]}}">
                                                <div class="column username-date">
                                                    <div  class="username">d***1</div>
                                                    <div  class="date">08.05.26</div>
                                                </div>
                                                <div class="buttons-group">
                                                    <button aria-label="Share Bet" class="btn">
                                                        <div class="icon share-i">
                                                            <img src="{{asset('custom_aviator/img/message-icon.svg')}}" alt="">
                                                        </div>
                                                    </button>
                                                    <div aria-label="Check Fairness" class="btn disabled-on-game-focused">
                                                        <div class="icon fairness-i">
                                                            <img src="{{asset('custom_aviator/img/shield-icon.svg')}}" alt="">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="top-wins-list-item-row">
                                                <div class="column top-bet-details">
                                                    <div class="bet-details-row">
                                                        <div class="description"> Bet USD </div>
                                                        <div class="value">0.40</div>
                                                    </div>
                                                    <div class="bet-details-row">
                                                        <div class="description"> Win USD </div>
                                                        <div class="value">8,134.64</div>
                                                    </div>
                                                </div>
                                                <div class="column top-bet-details">
                                                    <div class="bet-details-row">
                                                        <div class="description"> Result </div>
                                                        <div class="value" style="color: rgb(192, 23, 180);"> 20,000.00x </div>
                                                    </div>
                                                    <div class="bet-details-row">
                                                        <div class="description"> Round max. </div>
                                                        <div class="value" style="color: rgb(192, 23, 180);"> 48,323.74x </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="bets-footer">
                    <div class="bets-widget-footer">
                        <button class="btn provably-fair-block disabled-on-game-focused">
                            <div class="i-fair">
                                <img style="width:12px; height:12px; margin:4px 6px" src="{{asset('custom_aviator/img/pf-icon.svg')}}" alt="">
                            </div>
                            Provably Fair Game
                        </button>
                        <div class="logo-block disabled-on-game-focused">
                            <a target="_blank" class="logo-btn ng-star-inserted" href="https://spribe.co"> Powered by
                                <div class="i-logo">
                                    <img style="width: 35px; height:10px; margin-left:6px" src="{{asset('custom_aviator/img/footer-logo.svg')}}" alt="">
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="game-play">
                <div class="result-history">
                    <div class="stats dropdown">
                        <div class="payouts-wrapper">
                            <div class="payouts-block">
                                @foreach ($rounds as $round)
                                    @php
                                        $color = "rgb(52, 180, 255)";

                                        if ($round->crash_point >= 10) {
                                            $color = "rgb(192, 23, 180)";
                                        } elseif ($round->crash_point >= 2) {
                                            $color = "rgb(145, 62, 248)";
                                        }
                                    @endphp
                                    <div class="payout ng-star-inserted" style="color: {{$color}}"> {{$round->crash_point}} </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="button-block">
                            <div class="dropdown-toggle button">
                                <div class="button-icon" id="history-toggler">
                                    <img src="{{asset('custom_aviator/img/show-more-icon.svg')}}" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-menu" id="history-details">
                            <div class="wrapper">
                                <div class="header">
                                    <div class="text"> Round History </div>
                                    <button id="history-toggler">
                                        <img src="{{asset('custom_aviator/img/close-icon.svg')}}" alt="">
                                    </button>
                                </div>
                                <div class="payouts-block">
                                    @foreach ($rounds as $round)
                                        @php
                                            $color = "rgb(52, 180, 255)";

                                            if ($round->crash_point >= 10) {
                                                $color = "rgb(192, 23, 180)";
                                            } elseif ($round->crash_point >= 2) {
                                                $color = "rgb(145, 62, 248)";
                                            }
                                        @endphp
                                        <div class="payout ng-star-inserted" style="color: {{$color}}"> {{$round->crash_point}} </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="play-board-wrapper">
                    <div class="aviator">
                        <div class="game-loading">
                            <div class="loading">
                                <img src="{{asset('custom_aviator/img/top-loading.png')}}" alt="" class="top-loading-img">
                                <div class="loading-bars">
                                    <div class="loading-bar" width="100%"></div>
                                </div>
                                <img src="{{asset('custom_aviator/img/loading-bottom.png')}}" alt="">
                            </div>
                        </div>

                        <div class="glow-circle"></div>
                        <div class="rays"></div>

                        <div class="multiplier-wrapper">
                            <div class="multiplier-label">
                                FLEW AWAY!
                            </div>

                            <div class="multiplier" id="multiplier">1.00x </div>
                        </div>

                        <div class="plan-arapper">
                            <canvas id="gameCanvas"></canvas>
                            <img id="plane" src="{{ asset('custom_aviator/img/plane.gif') }}">
                        </div>
                    </div>
                </div>

                <div class="bet-controls">
                    <div class="controls">
                        <div class="bet-control double-bet bet-control-1" data-class="bet-control-1">
                            <div class="controls">
                                <div class="controls-content-top">
                                    <div class="navigation-wrapper">
                                        <div class="navigation ng-untouched ng-valid ng-star-inserted ng-dirty">
                                            <div class="navigation-switcher">
                                                <button class="tab ng-star-inserted active"> Bet </button>
                                                <button class="tab ng-star-inserted auto-toggler"> Auto </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="first-row bet-game auto-game-feature">
                                        <div class="bet-block">
                                            <div class="spinner ng-dirty ng-touched ng-valid">
                                                <div class="spinner big">
                                                    <div class="buttons">
                                                        <button type="button" class="ng-star-inserted minus">
                                                            <img src="{{asset('custom_aviator/img/minus.svg')}}" alt="">
                                                        </button>
                                                    </div>
                                                    <div class="input">
                                                        <input type="number" step="any" placeholder="2" min="1" value="10.00" class="bet-input">
                                                    </div>
                                                    <div class="buttons">
                                                        <button type="button" class="ng-star-inserted plus">
                                                            <img src="{{asset('custom_aviator/img/plus.svg')}}" alt="">
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bets-opt-list">
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="100"><span> 100 </span></button>
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="200"><span> 200 </span></button>
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="500"><span> 500</span></button>
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="10000"><span> 10000 </span></button>
                                            </div>
                                        </div>
                                        <div class="buttons-block active-button">
                                            <button class="btn bet-btn btn-success bet ng-star-inserted active">
                                                <span class="d-flex flex-column justify-content-center align-items-center">
                                                    <label class="label"> Bet </label>
                                                    <label class="amount">
                                                        <span class="bet-amount">10.00</span>
                                                        <span class="currency"> {{$currency}} </span>
                                                    </label>
                                                </span>
                                            </button>

                                            <button class="bet-btn btn btn-danger bet ng-star-inserted">
                                                <label class="label"> Cancel </label>
                                                <span class="btn-tooltip ng-star-inserted waiting"> Waiting for next round </span>
                                            </button>
                                            <button class="btn bet-btn btn-warning bet cashout ng-star-inserted">
                                                <span class="d-flex flex-column justify-content-center align-items-center">
                                                    <label> Cash Out </label>
                                                    <label>
                                                        <span class="amount"> </span>
                                                        <span class="currency"> {{$currency}} </span>
                                                    </label>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-100 border-line d-none"></div>
                                <div class="controls-content-bottom">
                                    <div class="second-row d-none">
                                        <div class="auto-bet-wrapper">
                                            <div class="auto-bet">
                                                <label class="ng-star-inserted">Auto bet</label>
                                                <div class="ng-untouched ng-pristine ng-star-inserted ng-valid">
                                                    <div class="input-switch off">
                                                        <span class="oval"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="cashout-block">
                                            <div class="cash-out-switcher">
                                                <label class=""> Auto Cash Out </label>
                                                <div class="ng-untouched ng-pristine ng-valid">
                                                    <div class="input-switch off">
                                                        <span class="oval"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cashout-spinner-wrapper">
                                                <div class="cashout-spinner disabled">
                                                    <div class="ng-untouched ng-dirty">
                                                        <div class="spinner small">
                                                            <div class="buttons"></div>
                                                            <div class="input full-width">
                                                                <input inputmode="decimal" disabled="" value="1.1" type="text">
                                                            </div>
                                                            <div class="text icon-x disabled ng-star-inserted">
                                                                <img src="{{asset('custom_aviator/img/close-icon.svg')}}" alt="" style="width: 100%;height:100%;">
                                                            </div>
                                                            <div class="buttons"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bet-control double-bet ng-star-inserted bet-control-2" data-class="bet-control-2">
                            <div class="controls">
                                <div class="controls-content-top">
                                    <div class="sec-hand-btn remove ng-star-inserted"></div>
                                    <div class="navigation-wrapper">
                                        <div class="navigation ng-untouched ng-valid ng-star-inserted ng-dirty">
                                            <div class="navigation-switcher">
                                                <button class="tab ng-star-inserted active"> Bet </button>
                                                <button class="tab ng-star-inserted auto-toggler"> Auto </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="first-row bet-game auto-game-feature">
                                        <div class="bet-block">
                                            <div class="spinner ng-dirty ng-touched ng-valid" _nghost-yap-c137="">
                                                <div class="spinner big">
                                                    <div class="buttons">
                                                        <button type="button" class="ng-star-inserted minus">
                                                            <img src="{{asset('custom_aviator/img/minus.svg')}}" alt="">
                                                        </button>
                                                    </div>
                                                    <div class="input">
                                                        <input type="number" step="any" placeholder="2" value="10.00" class="bet-input">
                                                    </div>
                                                    <div class="buttons">
                                                        <button type="button" class="ng-star-inserted plus">
                                                            <img src="{{asset('custom_aviator/img/plus.svg')}}" alt="">
                                                        </button>
                                                    </div>

                                                </div>
                                            </div>
                                            <div class="bets-opt-list">
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="100"><span> 100 </span></button>
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="200"><span> 200 </span></button>
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="500"><span> 500</span></button>
                                                <button class="btn btn-secondary btn-sm bet-opt ng-star-inserted" data-value="10000"><span> 10000 </span></button>
                                            </div>
                                        </div>
                                        <div class="buttons-block">
                                            <button class="btn btn-success bet-btn ng-star-inserted active">
                                                <span class="d-flex flex-column justify-content-center align-items-center">
                                                    <label class="label"> Bet </label>
                                                    <label class="amount">
                                                        <span class="bet-amount">10.00</span>
                                                        <span class="currency"> {{$currency}} </span>
                                                    </label>
                                                </span>
                                            </button>

                                            <button class="bet-btn btn btn-danger ng-star-inserted">
                                                <label class="label"> Cancel </label>
                                                <span class="btn-tooltip ng-star-inserted waiting"> Waiting for next round </span>
                                            </button>

                                            <button class="btn bet-btn btn-warning cashout ng-star-inserted">
                                                <span class="d-flex flex-column justify-content-center align-items-center">
                                                    <label> Cash Out </label>
                                                    <label class="amount-multiple">
                                                        <span class="amount">1.60</span>
                                                        <span class="currency"> {{$currency}} </span>
                                                    </label>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-100 border-line d-none"></div>
                                <div class="controls-content-bottom">
                                    <div class="second-row d-none">
                                        <div class="auto-bet-wrapper">
                                            <div class="auto-bet">
                                                <label class="ng-star-inserted">Auto bet</label>
                                                <div class="ng-untouched ng-pristine ng-star-inserted ng-valid">
                                                    <div class="input-switch off">
                                                        <span class="oval"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="cashout-block">
                                            <div class="cash-out-switcher">
                                                <label class=""> Auto Cash Out </label>
                                                <div class="ng-untouched ng-pristine ng-valid">
                                                    <div class="input-switch off">
                                                        <span class="oval"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cashout-spinner-wrapper">
                                                <div class="cashout-spinner disabled">
                                                    <div class="ng-untouched ng-dirty">
                                                        <div class="spinner small">
                                                            <div class="buttons"></div>
                                                            <div class="input full-width">
                                                                <input inputmode="decimal" disabled value="1.1" type="text">
                                                            </div>
                                                            <div class="text icon-x disabled ng-star-inserted">
                                                                 <img src="{{asset('custom_aviator/img/close-icon.svg')}}" alt="" style="width: 100%;height:100%;">
                                                            </div>
                                                            <div class="buttons"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <body>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="{{ asset('custom_aviator/js/main.js') }}"></script>
        <script src="{{ asset('custom_aviator/js/button.js') }}"></script>
    </body>
</body>

</html>
