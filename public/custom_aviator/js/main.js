
/* =========================
   GAME VARIABLES
========================= */
const APP_URL = window.location.origin;
let progress = 0;

let speed = 2;

let multiplier = 3;

let crashed = false;
let canAnimate = true;

const multiplierText = $("#multiplier");
const multiplierLabel = $('.multiplier-label');
const gameLoading = $('.game-loading');
const loadingBar = $('.loading-bar');
const plane = document.getElementById("plane");

let betStatus = 'running';
let betAmount = 0;
let checkAutoBet = true;


const socket = io("http://localhost:3000", {
    path: "/socket"
});

socket.on("connect", () => {
    socket.emit("join:room", {
        roomKey: "aviator",
        user_id: 1
    });
});


function animateCount(element, endValue, duration = 5000) {
    const startValue = 0;
    const incrementTime = 20; // update every 20ms
    const totalSteps = duration / incrementTime;
    const increment = endValue / totalSteps;

    let currentValue = startValue;

    const timer = setInterval(() => {
        currentValue += increment;

        if (currentValue >= endValue) {
            currentValue = endValue;
            clearInterval(timer);
        }

        $(element).text(Math.floor(currentValue).toLocaleString());
    }, incrementTime);
}

var total_win = 0;

socket.on('bet:update',(data) =>{
    total_win = 0;
    $('.progress-bar').css('width', 0 + '%');
    $('#total-win').text('0.00');
    const bets = data.bets;
    const container = $('.bet-items');
    animateCount('#total_bet', data.total_bets, 4000);
   
    container.empty();

    let index = 0;
    const interval = setInterval(function () {

        if (index >= bets.length) {
            clearInterval(interval);
            return;
        }

        const bot = bets[index];

        const username =
            bot.bet_name.substring(0, 1) +
            '***' +
            bot.bet_name.substring(bot.bet_name.length - 1);

        container.append(`
            <div class="bet-list-item bet-autocashout"
                data-bet_amount="${bot.bet_amount}"
                data-cashout="${bot.cashout_point}">

                <div class="item-column player">
                    <img class="avatar" src="${APP_URL}/${bot.image}" alt="">
                    <div class="username">${username}</div>
                </div>

                <div class="item-column bet">
                    <div>${parseFloat(bot.bet_amount || 0).toFixed(2)}</div>
                </div>

                <div class="item-column x"></div>

                <div class="item-column win"></div>
            </div>
        `);

        index++;

    }, Math.floor(Math.random() * 171) + 30);
});

socket.on("round:new", (data) => {
    multiplierLabel.hide();
    multiplierText.hide();
    gameLoading.addClass('active');
    loadingBar.addClass('animate');
    canAnimate = true;
    $('#round_id').val(data.roundId);

    $('.bet-control').each(function () {
        $(this).data('waiting','');
        $(this).find('.waiting').hide();
    });

    setTimeout(() => {
        gameLoading.removeClass('active');
    }, 6000);

    checkAutoBet = true;
});

socket.on("betting:timer", data => {
    betStatus = 'bet';

    $('.bet-control').each(function () {
        $(this).data('waiting','');
        $(this).find('.waiting').hide();
    });
});

socket.on("bet:close", () => {
    betStatus = 'running';
    gameLoading.removeClass('active');
});

socket.on("round:start", data => {
    multiplierText.show();
    multiplierText.removeClass('small medium big');
});

socket.on("multiplier:update", data => {
    gameLoading.removeClass('active');
    var realMultiplier = data.multiplier;
    multiplierText.html(realMultiplier.toFixed(2) + "x");
    progress = data.progress;
    speed = data.speed;
    multiplierText.show();
    multiplierText.removeClass('small medium big');
    if(canAnimate){
        animate();
        canAnimate = false;
    }
    
    multiplier = parseFloat(data.multiplier);
    updateCashout();
    betAutoCashout();
});

const payoutsBlock = $(".payouts-block");

socket.on("round:crash", (data) => {
    resetBtn();
    progress = data.progress;
    speed = data.speed;
    crashed = true;
    canAnimate = true;
    multiplierLabel.show();

    const payout = $("<div>")
        .addClass("payout")
        .text(data.multiplier.toFixed(2) + "x");

    // colors like aviator
    if (data.multiplier < 2) {
        payout.css("color", "#34b4ff");
        multiplierText.addClass('small');
    }
    else if (data.multiplier < 10) {
        payout.css("color", "#913ef8");
        multiplierText.addClass('medium');
    }
    else{
        payout.css("color", "#c017b4");
        multiplierText.addClass('big');
    }

    // add first
    payoutsBlock.prepend(payout);

    // smooth shift for old items
    payoutsBlock.children().each(function(index) {

        $(this).css({
            transition: "transform 0.4s ease"
        });

    });

    // limit history
    if (payoutsBlock.children().length > 20) {
        payoutsBlock.children().last().remove();
    }
});

const canvas = document.getElementById("gameCanvas");

const ctx = canvas.getContext("2d");



const aviator = document.querySelector(".aviator");

function resizeCanvas() {
    canvas.width = aviator.clientWidth;
    canvas.height = aviator.clientHeight;
}

resizeCanvas();

window.addEventListener(
    "resize",
    resizeCanvas
);



const maxWidth = canvas.width * 0.85; // 8.5

const topLimit = canvas.height * 0.15;


function getCurveY(x){

    const curvePower = 1.6;
    const curveHeight = 0.99;

    const t = x / canvas.width;

    let y =
        canvas.height -
        Math.pow(t, curvePower) *
        canvas.height *
        curveHeight;

    /*
      whole curve floating
      from 0 -> maxWidth
    */

    const floatAmplitude = 10;
    const floatSpeed = 0.002;

    /*
      stronger movement
      near the end
    */

    const influence = x / maxWidth;

    y += Math.sin(
            Date.now() *
            floatSpeed
        ) *
        floatAmplitude *
        influence;

    /*
      stop going too high
    */

    if(y < topLimit){

        y = topLimit;
    }

    return y;
}

/* =========================
   DRAW
========================= */

function drawCurve(){

    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

    if(crashed){
        return;
    }

    let endX = progress * canvas.width;

    if(endX > maxWidth){

        endX = maxWidth;
    }

    /*
      global flying movement
      for whole curve
    */

    const flyOffset =
        Math.sin(
            Date.now() * 0.002
        ) * 40;

    /* =====================
       FILLED AREA
    ===================== */

    ctx.beginPath();

    ctx.moveTo(
        0,
        canvas.height
    );

    for(let x=0; x<=endX; x++){

        let y =
            getCurveY(x);

        /*
          stronger movement
          near plane
        */

        const influence =
            x / maxWidth;

        y +=
            flyOffset *
            influence;

        /*
          keep inside top limit
        */

        if(y < topLimit){

            y = topLimit;
        }

        ctx.lineTo(x,y);
    }

    ctx.lineTo(
        endX,
        canvas.height
    );

    ctx.closePath();

    const gradient =
        ctx.createLinearGradient(
            0,
            0,
            0,
            canvas.height
        );

    gradient.addColorStop(
        0,
        "rgba(255,0,80,0.95)"
    );

    gradient.addColorStop(
        1,
        "rgba(120,0,20,0.15)"
    );

    ctx.fillStyle =
        gradient;

    ctx.fill();

    /* =====================
       OUTLINE
    ===================== */

    ctx.beginPath();

    for(let x=0; x<=endX; x++){

        let y =
            getCurveY(x);

        const influence =
            x / maxWidth;

        y +=
            flyOffset *
            influence;

        if(y < topLimit){

            y = topLimit;
        }

        if(x === 0){

            ctx.moveTo(x,y);

        }else{

            ctx.lineTo(x,y);
        }
    }

    ctx.strokeStyle =
        "#ff174f";

    ctx.lineWidth = 4;

    ctx.shadowColor =
        "#ff174f";

    ctx.shadowBlur = 20;

    ctx.stroke();

    ctx.shadowBlur = 0;

    /* =====================
       PLANE HTML ELEMENT
    ===================== */

    if(endX > 0){

        let planeY =
            getCurveY(endX);

        const influence =
            endX / maxWidth;

        planeY +=
            flyOffset *
            influence;

        if(planeY < topLimit){

            planeY = topLimit;
        }

        /*
          rotate based on curve
        */

        let nextY = getCurveY(endX + 1);

        nextY +=
            flyOffset *
            influence;

        const angle =
            Math.atan2(
                nextY - planeY,
                1
            );

        /*
          move HTML plane
        */

        const pos = {
            x: endX,
            y: planeY
        };

        plane.style.left =
            (pos.x + 30 ) + "px";

        plane.style.top =
            (pos.y -10) + "px";

        plane.style.transform =
            `
            translate(-50%, -50%)
            rotate(${angle * 0.1}rad)
            `;
    }
}

/* =========================
   PLANE POSITION
========================= */


/* =========================
   ANIMATION
========================= */

function animate(){
    drawCurve();

    /* crash */

    if(crashed){
        /* plane fly away */

        plane.style.transition =
            "all 0.8s ease";

        plane.style.top =
            "-300px";

        plane.style.left = (canvas.width + 300) + "px";

        plane.style.transform = "rotate(-45deg) scale(0.5)";

        setTimeout(
            resetGame,
            1400
        );

        return;
    }

    requestAnimationFrame(
        animate
    );
}

/* =========================
   RESET GAME
========================= */

function resetGame(){

    progress = 0;

    speed = 0.002;

    multiplier = 1;

    crashed = false;

    plane.style.transition = "left 0.03s linear, top 0.03s linear, transform 0.03s linear";

}

function checkBet() {
    $('.buttons-block').each(function () {
        if ($(this).hasClass('active-button')) {
            var $btns = $(this);
            var $parent = $btns.closest('.bet-control');

            var $betBtn = $parent.find('.btn-success');
            var $cancelBtn = $parent.find('.btn-danger');
            var $cashOutBtn = $parent.find('.btn-warning');

            if (betStatus === 'running') {
                $betBtn.removeClass('active');
                $cashOutBtn.addClass('active');
            } else {
                $cashOutBtn.removeClass('active')
                $betBtn.addClass('active');
            }
        }
    });
}

// checkBet();



