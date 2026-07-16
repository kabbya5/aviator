// ACtion js
var user_id = $('#user_id').val();
var userBalance = parseFloat($('#total-balance').text());

$(window).on('load', function () {
    $('#main-loading').fadeOut(500, function () {
        $('#main-section').fadeIn(500);
    });
});


function checkCashout(parent){
    parent.find('.btn').removeClass('active');
    var cashOutBtn = parent.find('.btn-warning');
    var $cancelBtn = parent.find('.btn-danger');
    var auto_bet = parent.data('auto_bet');
    var auto_cashouut = parent.data('auto_cashout');
    var bet_id = parent.data('bet_id');

    cashOutBtn.addClass('active');
    var amount = parseFloat(parent.data('bet_amount')) || 0;
    amount =  parseFloat(multiplier) * amount;

    cashOutBtn.find('.amount').text(amount.toFixed(2));

    if(auto_bet){
        if(checkAutoBet){
            userBalance -= amount;
            $('#total-balance').text(userBalance.toFixed(2));
            checkAutoBet = false;
        }
        if(auto_cashouut > 1 && auto_cashouut <= multiplier){
            betCashout(parent,amount,bet_id);
        }
    }
}

function updateCashout(){
    $('.bet-control').each(function(){
        var bet_id = $(this).data('bet_id');
        var waiting = $(this).data('waiting');
        if(bet_id  && !waiting){
            checkCashout($(this));
        }
    });
}

function buttonStatus(bet, status = 'pending'){
    var parent = $('.'+bet.class_name);
    parent.data('bet_amount',bet.bet_amount);
    parent.data('bet_id', bet.id);
    parent.data('auto_bet', bet.auto_bet);
    parent.find('.btn').removeClass('active');
    parent.find('.btn-danger').addClass('active');
    
    parent.find('.btn-danger').addClass('active');
    if (betStatus === 'running') {
        parent.find('.waiting').show();
    } else {
        parent.find('.waiting').hide();
    }

    if(status == 'running'){
        checkCashout(parent)
    }
}

function resetBtn(){
    $('.bet-control').each(function(){
        $btn = $(this).find('.btn');
        $btn.removeClass('active');
        var waiting = $(this).data('waiting') ?? null;
        var auto_bet = $(this).data('auto_bet') ?? null;

        if(waiting){
            $(this).find('.btn-danger').addClass('active');
            $(this).data('waiting','');
        }else if(auto_bet){
            $(this).find('.btn-danger').addClass('active');
        }else{
            $(this).data('bet_id','');
            $(this).find('.btn-success').addClass('active');
        }
    });
}

function deleteTempBets(){
    $.ajax({
        url: "/aviator/delete/temp/bets",
        type: "GET",
        data:{user_id:user_id},
        success: function (res) {
            console.log(res);
        },
    });
}


$(document).ready(function () {
    $.ajax({
        url: "/aviator/check/bets",
        type: "GET",
        success: function (res) {
            // res.bets.forEach(element => {
            //     buttonStatus(element);
            // });

            res.running_bets.forEach(element => {
                buttonStatus(element,'running');
            });
        },
    });
});

function updatePreviousBets(view, crash_point){
    var multy_class = 'small';
    $('.previous-bets').find('.result-multiplier').text(crash_point);

    if(crash_point > 7){
        multy_class = 'big';
    }else if(crash_point > 2){
        multy_class = 'medium';
    }
    $('.previous-bets').find('.result-multiplier').addClass(multy_class);

    $('.previous-bets').find('.bet-items').html(view);
}

function tabsData(type){
    $.ajax({
        url: "/aviator/tabs/data",
        type: "GET",
        data:{type:type},
        success: function (res) {
            if(type == 'previous-bets'){
                updatePreviousBets(res.view, res.crash_point);
            }

        },
    });
}

$(document).on('click', '.bet-tab', function () {
    const target = $(this).data('target');
    if(target != '.live-all-bets'){
        var tabType = target.replace('.','');
        tabsData(tabType);
    }
    $('.bet-tab').removeClass('active');
    $('.tab-details').removeClass('active');
    $(this).addClass('active');
    $(target).addClass('active');
    
});

$(document).on('click', '#history-toggler', function () {
    $('#history-details').toggleClass('active');
});

$(document).on('click', '.plus', function (){
    var parent = $(this).closest('.bet-control');
    var input = parent.find('.bet-input');
    var bet_amount = parent.find('.bet-amount');

    var amount = parseInt(input.val()) || 0;
    amount = amount + 10;

    input.val(amount.toFixed(2));
    bet_amount.text(amount.toFixed(2));
});

$(document).on('click', '.minus', function () {

    var parent = $(this).closest('.bet-control');
    var input = parent.find('.bet-input');
    var bet_amount = parent.find('.bet-amount');

    var amount = parseInt(input.val()) || 0;
    amount = Math.max(amount - 10, 1);

    input.val(amount.toFixed(2));
    bet_amount.text(amount.toFixed(2));
});

$(document).on('click', '.bet-opt', function () {
    var parent = $(this).closest('.bet-control');
    var input = parent.find('.bet-input');
    var amount = parseFloat($(this).data('value'));
    var bet_amount = parent.find('.bet-amount');

    input.val(amount.toFixed(2));
    bet_amount.text(amount.toFixed(2));
});

$(document).on('change', '.bet-input', function () {
    var parent = $(this).closest('.bet-control');
    var input = parent.find('.bet-input');
    var bet_amount = parent.find('.bet-amount');

    var amount = parseInt(input.val()) || 0;

    bet_amount.text(amount.toFixed(2));
});

$(document).on('click', '.navigation-switcher .tab', function () {
    const $controls = $(this).closest('.controls');

    $(this)
        .siblings('.tab')
        .removeClass('active');

    $(this).addClass('active');

    $controls.find('.second-row').toggleClass(
        'd-none',
        !$(this).hasClass('auto-toggler')
    );
});

$(document).on('click', '.cash-out-switcher', function(){
    const raw = $(this).closest('.second-row');
    const autoBet = raw.find('.auto-bet .input-switch');

    if(autoBet.hasClass('off')){
        $(this).find('.input-switch').toggleClass('off');
        var input = raw.find('input');
        if($(input).prop('disabled')){
            input.prop('disabled', false);
        }else{
            input.prop('disabled', true);
        }
        
    }else{
        return;
    }
});

function placeBet(className, amount, autoBet = 0, auto_cashout = 0) {
    return $.ajax({
        url: '/aviator/place/bet',
        method: 'POST',
        data: {
            className: className,
            autoBet: autoBet,
            amount: amount,
            auto_cashout: auto_cashout,
            betStatus: betStatus,
            user_id: user_id,
            round_id: round_id,
            _token: $('meta[name="csrf-token"]').attr('content'),
        }
    });
}


$(document).on('click', '.auto-bet', function(){
    var $parent = $(this).closest('.bet-control');
    var className = $parent.data('class'); 
    var $input = $parent.find('.bet-input');
    var amount = parseFloat($input.val()) || 0;
    if($(this).find('.input-switch').hasClass('off')){
        
        var auto_cashout_input = $parent.find('.cashout-spinner-wrapper .input input');
        var auto_cashout = null;
        if(!auto_cashout_input.prop('disabled')){
            auto_cashout = auto_cashout_input.val();
        }

        placeBet(className, amount,1,auto_cashout).done(function(res) {
            $parent.data('auto_bet',1);
            $parent.data('auto_cashout',auto_cashout);
            $parent.data('bet_id', res.bet_id);
            $parent.data('bet_amount',amount);
            
            $parent.find('.btn-success').removeClass('active');
            $parent.find('.btn-danger').addClass('active');

            if (betStatus === 'running') {
                $parent.data('waiting', 'next');
                $parent.find('.waiting').show();
            } else {
                $parent.find('.waiting').hide();
            }

            userBalance -= amount;
            $('#total-balance').text(userBalance.toFixed(2));
        });
    }else{
        cancleBet(className, amount).done(function(res){
            $parent.find('.btn').removeClass('active');
            $parent.find('.btn-success').addClass('active');
            $parent.find('.waiting').hide();
            $parent.data('auto_bet',0);
            $parent.data('auto_cashout',0);
            $parent.data('bet_id', '');
            $parent.data('bet_amount','');

            userBalance += amount;
            $('#total-balance').text(userBalance.toFixed(2));
        });
    }
    $(this).find('.input-switch').toggleClass('off');
});

function cancleBet(class_name, amount){
    return $.ajax({
        url: '/aviator/cancel/bet',
        method: 'POST',
        data: {
            class_name: class_name,
            amount: amount,
            user_id: user_id,
            _token: $('meta[name="csrf-token"]').attr('content'),
        }
    });
}

function betCashout(parent,amount,bet_id){
    var class_name = parent.data('class');
    $.ajax({
        url: '/aviator/cashout/bet',
        method: 'get',
        data: {
            round_id:round_id,
            bet_id:bet_id,
            user_id: user_id,
            amount:amount,
            class_name:class_name,
            _token: $('meta[name="csrf-token"]').attr('content'),
        },
        success:function(res){
            $btn = parent.find('.btn');
            $btn.removeClass('active');
            if(res.bet.auto_bet){
                parent.find('.btn-danger').addClass('active');
                parent.data('waiting','next');
                
            }else{
                parent.find('.btn-success').addClass('active');
                parent.data('bet_id','');
            }

            var notification = $('.bet-notify-box');

            notification.find('.multi').text(multiplier + 'x');
            notification.find('.win-amount').text(res.bet.win_amount);
            notification.addClass('show');
            setTimeout(() => {
                notification.removeClass('show');
            }, 2000);

            userBalance += parseFloat(res.bet.win_amount);
            $('#total-balance').text(userBalance.toFixed(2));

            if(res.updateCrushPoint){
                const r = Math.random();
                const crashPoint = Math.min((1 / (1 - r)).toFixed(2), 200);
                socket.emit('update_crash_point',{
                    crashPoint:crashPoint
                });
            }
        }
    });
}

$(document).on('click', '.bet-btn', function () {
    var $btn = $(this);
    var $parent = $btn.closest('.bet-control');
    var className = $parent.data('class'); 
    var bet_id = $parent.data('bet_id');

    var $betBtn = $parent.find('.btn-success');
    var $cancelBtn = $parent.find('.btn-danger');
    var $cashOutBtn = $parent.find('.btn-warning');

    var $input = $parent.find('.bet-input');

    var amount = parseFloat($input.val()) || 0;

    // Detect button type
    var isBet = $btn.hasClass('btn-success');
    var isCancel = $btn.hasClass('btn-danger');
    var isCashOut =  $btn.hasClass('btn-warning')

    /*
    |--------------------------------------------------------------------------
    | BET BUTTON
    |--------------------------------------------------------------------------
    */

    if (isBet){
        if (amount <= 0) {
            alert('Enter valid amount');
            return;
        }

        placeBet(className, amount).done(function(res) {
            $betBtn.removeClass('active');
            $cancelBtn.addClass('active');

            if (betStatus === 'running') {
                $parent.data('waiting', 'next');
                $cancelBtn.find('.waiting').show();
            } else {
                $cancelBtn.find('.waiting').hide();
            }

            $parent.data('bet_id', res.bet_id);
            $parent.data('bet_amount',amount);

            userBalance -= amount;
            $('#total-balance').text(userBalance.toFixed(2));
        });
    }
    /*
    |--------------------------------------------------------------------------
    | CANCEL BUTTON
    |--------------------------------------------------------------------------
    */
    if (isCancel) {
        cancleBet(className, amount).done(function(res){
            $parent.data('bet_id','');
            $parent.data('auto_bet','');
            $parent.data('auto_cashout','');
            $cancelBtn.removeClass('active');
            $betBtn.addClass('active');
            $cancelBtn.find('.waiting').hide();

            $parent.find('.input-switcher').addClass('off');

            userBalance += amount;
            $('#total-balance').text(userBalance.toFixed(2));
        });
    }

    if(isCashOut){
        var amount = $btn.find('.amount').text();
        betCashout($parent,amount,bet_id);
    }
});

function rand(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

var total_payout = 0;

function betAutoCashout() {

    let progress = 0;

    if (multiplier < 1.5 && multiplier > 1.2) {
        progress = 10
    } else if (multiplier < 3) {
        progress = 10 + ((multiplier - 1.5) / 1.5) * 50;
    } else if (multiplier < 10) {
        progress = 60 + ((multiplier - 3) / 7) * 20;
    } else {
        progress = Math.min(80 + ((multiplier - 10) * 2), 98);
    }


    $('.progress-bar').css('width', progress + '%');

    $('.bet-autocashout').each(function () {
        let cashoutPoint = parseFloat($(this).data('cashout')) || 0;

        if (multiplier >= cashoutPoint) {
            $(this).removeClass('bet-autocashout');
            $(this).addClass('cashout');
            $(this).find('.x').text(cashoutPoint);
            var x_chass_name = 'small';
            if(cashoutPoint > 7){
                x_chass_name = 'big'
            }else if(cashoutPoint > 2){
                x_chass_name = 'medium';
            }
            $(this).find('.x').addClass(x_chass_name);

            var bet_amount = parseFloat($(this).data('bet_amount'));
            var win_amount = cashoutPoint * bet_amount;

            total_win += win_amount * 1.3;

            $(this).find('.win').text(win_amount.toFixed(2));
            $('#total-win').text(total_win.toFixed(2));
        }
    });

    var actual_bets = parseInt($('#payout-bet').data('total_bets')) || 0;
    
    if(progress > 0){
        total_payout = parseInt(actual_bets * (progress / 100));
        $('#payout-bet').text(total_payout); 
    }
   
}


