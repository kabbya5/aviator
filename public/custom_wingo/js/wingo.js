$(document).ready(function(){
    const socket = io('https://boomx.club', {
        path: '/socket',
        withCredentials: true
    });
    
    let currency_symbol = $('#currency-symbol').text().trim();

    let activeGame = '30s';
    let isPlayMusic = true;
    const user_id = $('#auth_id').val();

    const ROOM_MAP = {
        '30s': 'win_go_30_sec',
        '1m': 'win_go_1_minute',
        '3m': 'win_go_3_minute',
        '5m': 'win_go_5_minute',
    };

    const WINGO_CONFIG = {
        '30s': { time: 30, running:30, slug: 'win_go_30_sec', title: 'Wingo 30 Sec', roundId:'',},
        '1m':  { time: 60, running:60, slug: 'win_go_1_minute', title: 'Wingo 1 Minute', roundId:'',},
        '3m':  { time: 180, running:180, slug: 'win_go_3_minute', title: 'Wingo 3 Minute', roundId:'', },
        '5m': { time: 300, running:300, slug: 'win_go_5_minute', title: 'Wingo 5 Minute', roundId:'', }
    };


    socket.on('timer:update', (data) => {

        let time = Number(data.time);

        if (!Number.isFinite(time)) {
            return;
        }

        updateTimer(time);
        if(time > 5){
            $('#counDownModal').fadeOut(200);
        }else if(time < 5 && time >= 1){
            $('#counDownModal').fadeIn(200);
        }
    });

    socket.on('round:start', data => {
        $('#round-id').text(data.roundId);
        WINGO_CONFIG[activeGame].roundId = data.roundId;
    });

    socket.on('bet:close', (data) => {
        openCountModal(data.time);
        $('#bet-modal').fadeOut(200);
    });

    socket.on('round:end', () => {
        openResultModal();
    });

    socket.on('connect', () => {
        socket.emit('join:room', {
            roomKey: ROOM_MAP[activeGame],
            user_id:  user_id,
        });
    });

    /* ================= UI UPDATE ================= */
    function updateTimer(seconds){
        let m = Math.floor(seconds / 60).toString().padStart(2,'0');
        let s = (seconds % 60).toString().padStart(2,'0');

        $('#t-m1').text(m[0]);
        $('#t-m2').text(m[1]);
        $('#t-s1').text(s[0]);
        $('#t-s2').text(s[1]);
    }

    /* ================= BET MODAL ================= */
    const audio = new Audio('/custom_wingo/asset/mp3/music.mp3');
    audio.preload = 'auto';

    let betInterval = null;

    function openCountModal(betTime) {

        $('#counDownModal').fadeIn(200);

        // Clear previous interval if exists
        if (betInterval) {
            clearInterval(betInterval);
        }

        betInterval = setInterval(() => {
            betTime--;

            // Play sound every second
            audio.currentTime = 0;

            if (isPlayMusic === true) {
                audio.play();
            }

            let s1 = Math.floor(betTime / 10);
            let s2 = betTime % 10;

            $('#modal_s1').text(s1);
            $('#modal_s2').text(s2);

            if (betTime <= 0) {
                clearInterval(betInterval);
                $('#counDownModal').fadeOut(200);
            }
        }, 1000);

        $('#modal_s1').text(0);
        $('#modal_s2').text(5);
    }

    /* ================= GENERATE DATA  ================= */

    function generateData(){
        var wingo_slug = WINGO_CONFIG[activeGame].slug;
        var url = '/user/wingo/recent/result/' + wingo_slug;
        $.ajax({
            url:url,
            type:"get",

            success:function(res){
                $('#result_data').html(res.result_data);
                $('#result_data_pagination').html(res.result_data_pagination);

                $('#history_statistic').html(res.history_statistic);
                $('#chart_data').html(res.chart_data);
                $('#chart_data_pagination').html(res.chart_data_pagination);

                $('#my_history_data').html(res.my_history_data);
                $('#my_history_data_pagination').html(res.my_history_data_pagination);
                $('#top-history').html(res.top_history);
            }
        });

        setTimeout(() => {
            drawLeaderLines();
        }, 1000);
    }

    /* ================= GENERATE RESULT ================= */

    var win_img = '/custom_wingo/asset/win.png';
    var loss_img = '/custom_wingo/asset/loss.png';

    function generateResult(){

        var main_balance = parseFloat($('#main-balance').text());

        $.ajax({
            url:"/user/get/result",
            type:"get",
            data:{
                round_id:$.trim($('#round-id').text()),
                _token: window.csrfToken,
            },
            success:function(res){
                $('#resultModal .result').removeClass('win');
                $('#resultModal .result').removeClass('loss');
                if(res.win > 0){
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 },
                    });
                    main_balance += res.win;
                    $('#resultModal .result').addClass('win');
                    $('#resultModal .head-title').html('Congratulations');
                    $('#resultModal .result').css('background-image','url("' + win_img + '")');
                    $('#resultModal .bonus-div .title').html('Bonus');
                    $('#resultModal .bonus-div .amount').show();
                    $('#resultModal .bonus-div #win-amount').html(res.win.toFixed(2));
                }else{
                    $('#resultModal .result').addClass('loss');
                    $('#resultModal .head-title').html('Sorry');
                    $('#resultModal .result').css('background-image','url("' + loss_img + '")');
                    $('#resultModal .bonus-div .title').html('Loss');
                    $('#resultModal .bonus-div .amount').hide();
                }

                $('#resultModal .lottery-result').removeClass('green');
                $('#resultModal .lottery-result').removeClass('red');
                $('#resultModal .lottery-result').removeClass('green_violet');
                $('#resultModal .lottery-result').removeClass('red_violet');

                $('#resultModal .lottery-result').addClass(res.result.color);
                $('#resultModal .bonus-div .text-sub').html(`Period: ${res.round.wingo.name} ${res.round.round_no}`);
                $('#resultModal .lottery-result .color').html(res.result.color);
                $('#resultModal .lottery-result .size').html(res.result.size);
                $('#resultModal .lottery-result .number').html(res.result.number);

                let color = res.result.color;

                color = color
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, char => char.toUpperCase());

                $('#resultModal .lottery-result .color').html(color);

                if(res.total_bet){
                    $('#resultModal').fadeIn();
                }

                parseFloat($('#main-balance').text(main_balance.toFixed(2)));
            },
            error:function(error){
                console.log('error');
            }
        })
    }

    /* ================= RESULT MODAL ================= */
    function openResultModal(){
        resultTime = 5;

        generateResult();

        dataTimeout = setTimeout(() => {
            generateData();
        }, 1000);

        let resultInterval = setInterval(() => {
            resultTime--;

            if(resultTime <= 0){
                clearInterval(resultInterval);
                $('#resultModal').fadeOut();
            }
        },1000);
    }

    $('#time-rounds .time-card').click(function () {
        $('.time-card').removeClass('active');
        $(this).addClass('active');

        activeGame = $(this).data('key');
        let cfg = WINGO_CONFIG[activeGame];

        $('#game-type').text(cfg.title);
        $('.bet-form-header .game-name').html(cfg.title);

        socket.emit('join:room', {
            roomKey: ROOM_MAP[activeGame],
            user_id:  user_id,
        });

        dataTimeout = setTimeout(() => {
            generateData();
        }, 1000);
    });


    // update bet balance

    function checkBalance(){
        var betAmount = parseFloat($('#bet-total').text());
        const userBalance = parseFloat($('#main-balance').text());

        if(userBalance < betAmount){
            $('#bet-total').html(userBalance.toFixed(2));
            $('.balance-error').show();
        }else{
            $('#bet-total').html(betAmount.toFixed(2));
            $('.balance-error').hide();
        }
    }

    function calculateBetAmount(){
        var quantity = parseInt($('#amount').val() || 1);
        const $active = $('.bet-amount.active');

        const betAmount = $active.length ? parseInt($active.data('value')) : 1;

        var amount = betAmount * quantity;

        $('#bet-total').html(amount.toFixed(2));

        $('.bet-multiple').each(function () {
            if (parseInt($(this).data('value')) === quantity) {
                $(this).addClass('active');
            }else{
                $(this).removeClass('active');
            }
        });

        checkBalance();
    }

    $('.bet-amount').click(function(){
        $('.bet-amount').removeClass('active');
        $(this).addClass('active');
        calculateBetAmount();
    });

    $('.bet-multiple').click(function(){
        $('.bet-multiple').removeClass('active');
        $(this).addClass('active');
        $('#amount').val($(this).data('value'));
        calculateBetAmount();
    });

    $('.qty-btn.minus').click(function(){
        var quantity = parseInt($('#amount').val());

        if (quantity <= 1) {
            return;
        }

        quantity--;

        $('#amount').val(quantity);
        calculateBetAmount();
    });

    $('.qty-btn.plus').click(function(){
        var quantity = parseInt($('#amount').val());
        quantity++;
        $('#amount').val(quantity);
        calculateBetAmount(quantity);
    });

     $('#amount').on('input', function () {
        calculateBetAmount();
    });

    $('.game-bet-btn').click(function(){
        const bet_value = $(this).data('value');
        $('#selected-bet').val(bet_value);

        $('#bet-form').removeClass();
        $('#bet-form').addClass(`bet-form`);
        $('#bet-form').addClass(`color_${bet_value}`);

        $('#bet-form .multi-btn').removeClass('active');
        $('#bet-form .amount_btn_1').addClass('active');
        $('#bet-form .multi_btn_1').addClass('active');
        $('#amount').val(1);
        $('#bet-modal').fadeIn(200);
        calculateBetAmount();
    });

    function handelRandomBet(){
        let numberBtn = $('.num-btn.game-bet-btn.active');
        let multiBtn = $('.bet-block .multi-btn.active');

        const bet_value = numberBtn.data('value');
        $('#selected-bet').val(bet_value);

        $('#bet-form').removeClass();
        $('#bet-form').addClass(`bet-form`);
        $('#bet-form').addClass(`color_${bet_value}`);

        let multiValue = multiBtn.text().trim();

        $('.bet-multiple').removeClass('active');
         $('#bet-form .bet-amount').removeClass('active');
        $('#bet-form .amount_btn_1').addClass('active');

        $('.bet-multiple').each(function () {
            if ($(this).text().trim() === multiValue) {
                $(this).addClass('active');
            }
        });

        $('#amount').val(1);

        calculateBetAmount();

        $('#bet-modal').fadeIn(200);
    }

    $(document).on('click', '.random-btn', function () {

        let buttons = $('.num-btn.game-bet-btn');
        let total = buttons.length;
        let index = 0;


        buttons.removeClass('hover active');

        let randomDuration = Math.floor(Math.random() * 3000) + 1000;

        let rolling = setInterval(function () {
            buttons.removeClass('hover');
            $(buttons[index]).addClass('hover');

            index++;
            if (index >= total) index = 0;
        }, 100);

        setTimeout(function () {
            clearInterval(rolling);

            buttons.removeClass('hover');

            let randomIndex = Math.floor(Math.random() * total);
            let selectedBtn = $(buttons[randomIndex]);

            selectedBtn.addClass('active');

            handelRandomBet();

        }, randomDuration);
    });

    $(document).on('click', '.bet-block .multi-btn', function () {
        $('.bet-block .multi-btn').removeClass('active');
        $(this).addClass('active');
    });


    function showBetConfirmation(text = 'Bet succeed') {
        var modal = $('#bet-conformation');
        $('.bet-conformation-text').html(text);


        modal.fadeIn(200);

        setTimeout(function() {
            modal.fadeOut(200);
        }, 1000);
    }

    $(document).on('submit', '#bet-form', function (e) {
        e.preventDefault();

        let url = $(this).attr('action');
        let formData = new FormData(this);
        let roundId = WINGO_CONFIG[activeGame].roundId;
        let wingo_slug = WINGO_CONFIG[activeGame].slug;
        let bet_amount = $('#bet-total').text();

        formData.append('round_id', roundId);
        formData.append('wingo_slug',wingo_slug);
        formData.append('bet_amount',bet_amount);

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            beforeSend: function () {
                $('.btn-confirm')
                    .prop('disabled', true)
                    .addClass('loading')
                    .text('Processing...');
            },

            success: function (res) {

                var main_balance = parseFloat($('#main-balance').text());
                main_balance -= res.bet_amount

                $('#main-balance').text(main_balance.toFixed(2));

                $('#bet-form')[0].reset();
                showBetConfirmation('Bet succeed');
                $('#bet-modal').fadeOut(200);
                $('#my_history_data').prepend(res.bet_html);
            },

            error: function (xhr) {
                showBetConfirmation('Bet failed');
            },

            complete: function () {
                $('.btn-confirm')
                    .prop('disabled', false)
                    .removeClass('loading')
                    .html(' Total amount ' + currency_symbol + ' <span id="bet-total"></span>')
            }
        });
    });

    $(document).on('click', '.btn-cancel, .close-btn ', function(){
        $('.custom-modal').fadeOut(200);
    });

    $(document).on('click', '.image-change', function(){
        isPlayMusic = !isPlayMusic;
        $('.voice-img').toggle();
        $('.voice-off-img').toggle();
    });

    $(document).on('click', '.page-link', function (e) {
        e.preventDefault();

        if ($(this).hasClass('disabled')) return;
        let url = $(this).attr('href');

        if (!url) return;

        var tbody = $(this).data('table');

        $.ajax({
            url: url,
            type: 'GET',
            success: function (res) {
                $(`#${tbody}`).html(res[tbody]);
                $(`#${tbody}_pagination`).html(res[`${tbody}_pagination`]);
                drawLeaderLines();
            }
        });
    });

    $(document).on('click', '.tab-btn', function(){
        $('.tab-btn').removeClass('active');
        $('.tab-pane').removeClass('active');
        $(this).addClass('active');

        var targetPannel = $(this).data('target');

        $(targetPannel).addClass('active');

        if (targetPannel !== '#chart') {
            clearLines();
            return;
        }

        setTimeout(() => {
            drawLeaderLines();
        }, 50);
    });

    $(document).on('click', '#my_history_data .data', function () {
        const details = $(this).find('.details');

        $('#my_history_data .details').not(details).slideUp();
        details.slideToggle();
    });

    $(document).on('click', '.how-play-btn', function () {
        var wingo_slug = WINGO_CONFIG[activeGame].slug;
        var text = '';
        if(wingo_slug == 'win_go_1_minute'){
            text = '1 minute 1 issue, 45 seconds to order, 15 seconds waiting for the draw. It opens all day.The total number of trades is 1440 issues.';
        }else if(wingo_slug == 'win_go_3_minute'){
            text = '3 minutes 1 issue, 2 minutes and 45 seconds to order, 15 seconds waiting for the draw. It opens all day. The total number of trade is 480 issues.';
        }else if(wingo_slug == 'win_go_5_minute'){
            text = '5 minutes 1 issue, 4 minutes and 45 seconds to order, 15 seconds waiting for the draw. It opens all day. The total number of trade is 288 issues.'
        }
        else{
            text = '30 seconds 1 issue, 25 seconds to order, 5 seconds waiting for the draw… It opens all day. The total number of trade is 2880 issues.';
        }
        $('#how-play .game-details').html(text);
        $('#how-play').fadeIn(200);
    });

    $(document).on('click', '.close', function () {
        $('#how-play').fadeOut(200);
    });
})
