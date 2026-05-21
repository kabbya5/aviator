
/* =========================
   GAME VARIABLES
========================= */

let progress = 0;

let speed = 2;

let multiplier = 1;

let crashed = false;
let canAnimate = true;

const multiplierText = $("#multiplier");
const multiplierLabel = $('.multiplier-label');
const gameLoading = $('.game-loading');
const loadingBar = $('.loading-bar');
const plane = document.getElementById("plane");

let betStatus = 'running';
let betAmount = 0;
let autoBet = false;
let autoCashout = false;


const socket = io("http://localhost:3000", {
    path: "/socket"
});

socket.on("connect", () => {
    socket.emit("join:room", {
        roomKey: "aviator",
        user_id: 1
    });
});

socket.on("round:new", data => {
    multiplierLabel.hide();
    multiplierText.hide();
    gameLoading.addClass('active');
    loadingBar.addClass('animate');
    canAnimate = true;

    setTimeout(() => {
        gameLoading.removeClass('active');
    }, 3000);
});

socket.on("betting:timer", data => {
    betStatus = 'bet';
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
});

const payoutsBlock = $(".payouts-block");

socket.on("round:crash", (data) => {
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

$(window).on('load', function () {

    $('#main-loading').fadeOut(500, function () {

            $('#main-section').fadeIn(500);

        });

    // setTimeout(function () {

    //     $('#main-loading').fadeOut(500, function () {

    //         $('#main-section').fadeIn(500);

    //     });

    // }, 3000);

});

$(document).on('click', '.bet-tab', function () {

    const target = $(this).data('target');

    $('.bet-tab').removeClass('active');
    $('.tab-details').removeClass('active');
    $(this).addClass('active');
    $(target).addClass('active');

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

// ACtion js

$(document).on('click', '#history-toggler', function () {
    $('#history-details').toggleClass('active');
});

$(document).on('click', '.plus', function () {

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

$(document).on('click', '.bet-btn', function () {

    var $btn = $(this);
    var $parent = $btn.closest('.bet-control');

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

    if (isBet) {

        if (amount <= 0) {
            alert('Enter valid amount');
            return;
        }

        // Switch buttons
        $betBtn.removeClass('active');
        $cancelBtn.addClass('active');

        // Show waiting only if round already started
        if (betStatus === 'running') {
            $cancelBtn.find('.waiting').show();
        } else {
            $cancelBtn.find('.waiting').hide();
        }

        console.log('Bet placed:', amount);

        // socket.emit('bet:place', { amount });

    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL BUTTON
    |--------------------------------------------------------------------------
    */
    if (isCancel) {

        // Restore original state
        $cancelBtn.removeClass('active');
        $betBtn.addClass('active');

        $cancelBtn.find('.waiting').hide();

        console.log('Bet cancelled');

        // socket.emit('bet:cancel');

    }

});


