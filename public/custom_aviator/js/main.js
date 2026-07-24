/* =========================
   GAME VARIABLES
========================= */
const APP_URL = window.location.origin;
let progress = 0.315;
let speed = 2;
let multiplier = 1.00;
let crashed = false;
let canAnimate = true;
let isFirstLoad = true;

const multiplierText = $("#multiplier");
const multiplierLabel = $('.multiplier-label');
const gameLoading = $('.game-loading');
const loadingBar = $('.loading-bar');
const plane = $("#plane");
var user_id = $('#user_id').val();

let betStatus = 'running';
let betAmount = 0;
let checkAutoBet = true;
var round_id = $('#round_id').val();

/* ========================================================
   AVIATOR ENGINE JQUERY PLUGIN DEFINITION (Moved to top)
=========================================================== */
(function($) {
    'use strict';

    class AviatorEngine {
        constructor(element, options = {}) {
            this.$canvas = $(element);
            this.ctx = this.$canvas[0].getContext('2d');
            this.$plane = $('#plane');
            
            this.options = $.extend({
                fontFamily: '"Arial", sans-serif',
                curveColor: '#E20630',
                fillColor: 'rgba(226, 6, 48, 0.25)',
                planeColor: '#E20630'
            }, options);
    
            this.planeX = 0;
            this.planeY = 0;
            this.prevPlaneX = 0;
            this.prevPlaneY = 0;
            this.maxReachedTime = null; 

            this.init();
        }

        init() {
            this.resizeCanvas();
            $(window).on('resize', () => this.resizeCanvas());
        }

        resizeCanvas() {
            const $aviator = this.$canvas.closest('.aviator');

            const width = $aviator.width();
            const height = $aviator.height();
            this.$canvas.attr({ width, height });
            
            const screenWidth = window.innerWidth; 

            if (screenWidth < 768) {
                this.device = 'mobile';
            } else if (screenWidth < 1224) {
            this.device = 'tablet';
            } else {
                this.device = 'desktop';
            }
        }

        updateFlight(elapsedMs) {
            const canvasW = this.$canvas.width();
            const canvasH = this.$canvas.height();
            
            let w = canvasW * 0.80; 
            let h = canvasH * 0.95; 
            let maxDeltaY = canvasH * 0.15; 
            let maxDeltaX = canvasW * 0.10; 

            if(this.device == 'mobile'){
                w = canvasW * 0.60; 
                h = canvasH * 0.85; 
                maxDeltaY = canvasH * 0.25;
                maxDeltaX = canvasW * 0.15;

            }else if(this.device == 'tablet'){
                w = canvasW * 0.78; 
                h = canvasH * 0.90; 
                maxDeltaY = canvasH * 0.17;
                maxDeltaX = canvasW * 0.1;
            }

            this.ctx.clearRect(0, 0, canvasW, canvasH);
            
            const maxLimit = 1.0;
            let progress = elapsedMs / 8000; 
            if (progress >= maxLimit) {
                progress = maxLimit;
                if (this.maxReachedTime === null) {
                    this.maxReachedTime = elapsedMs;
                }
            }

            // BASELINE DEFINITION: Changed start point from (canvasW * 0.05) directly to 0
            let targetX = 0 + (w * progress);
            let targetY = canvasH - (h * Math.pow(progress, 2)); // Curved exponential lift-off from canvasH (bottom)

            // Handle extended flight bobbing logic smoothly 
            if (this.maxReachedTime !== null) {
                const extendedTimeSec = (elapsedMs - this.maxReachedTime) / 1000;
                const cycleTime = extendedTimeSec % 4;

                if (cycleTime < 2) {
                    const factor = cycleTime / 2; 
                    targetY += maxDeltaY * factor;     
                    targetX += maxDeltaX * factor;     
                } else {
                    const factor = (cycleTime - 2) / 2; 
                    targetY += maxDeltaY * (1 - factor); 
                    targetX += maxDeltaX * (1 - factor); 
                }
            }

            if (this.prevPlaneX === 0) {
                this.prevPlaneX = targetX;
                this.prevPlaneY = targetY;
            }

            const deltaX = targetX - this.prevPlaneX;
            const deltaY = targetY - this.prevPlaneY;
            let angle = Math.atan2(deltaY, deltaX);

            this.planeX = targetX;
            this.planeY = targetY;
            
            this.prevPlaneX = targetX;
            this.prevPlaneY = targetY;

            let t = Math.min(targetX / 70, 1);

            let translateX = -15 + (-20 - (-15)) * t; // -10 -> -25
            let translateY = -75 + (-60 - (-75)) * t; // -60 -> -50

            if(this.device == 'mobile'){
                let t = Math.min(targetX / 75, 1);
                translateX = -10 + (-10 - (-10)) * t;
                translateY = -75 + (-70 - (-75)) * t; 
            }

            this.$plane.show().css({
                left: `${targetX}px`,
                top: `${targetY}px`,
                transform: `translate(${translateX}%, ${translateY}%) rotate(-5deg)`
            });

            this.drawFlightPath();
        }

        drawFlightPath() {
            const h = this.$canvas.height();
            
            // Set explicit 0 (left) and h (bottom) start anchors
            const startX = 0;
            const startY = h;
            
            this.ctx.beginPath();
            this.ctx.moveTo(startX, startY);
            
            // Draw smooth bezier curvature tracking from absolute bottom left
            this.ctx.quadraticCurveTo(
                this.planeX * 0.5, startY, 
                this.planeX, this.planeY
            );
            
            this.ctx.strokeStyle = this.options.curveColor;
            this.ctx.lineWidth = 3;
            this.ctx.stroke();

            // Fill underneath the flight vector path cleanly
            this.ctx.lineTo(this.planeX, startY);
            this.ctx.lineTo(startX, startY);
            this.ctx.fillStyle = this.options.fillColor;
            this.ctx.fill();
            this.ctx.closePath();
        }

        drawMultiplierText(text, color, subtitle = "") {
            const w = this.$canvas.width();
            const h = this.$canvas.height();

            this.ctx.textAlign = 'center';
            this.ctx.textBaseline = 'middle';

            if (subtitle) {
                this.ctx.font = `bold ${h * 0.05}px ${this.options.fontFamily}`;
                this.ctx.fillStyle = "#FFFFFF";
                this.ctx.fillText(subtitle, w / 2, h / 2 - (h * 0.08));
            }

            this.ctx.font = `bold ${h * 0.13}px ${this.options.fontFamily}`;
            this.ctx.fillStyle = color;
            this.ctx.fillText(text, w / 2, h / 2 + (subtitle ? h * 0.04 : 0));
        }

        triggerCrash(finalMultiplier) {
            this.maxReachedTime = null;
            this.prevPlaneX = 0;
            this.prevPlaneY = 0;
            const w = this.$canvas.width();
            const h = this.$canvas.height();
            this.ctx.clearRect(0, 0, w, h);
        }    
    }

    $.fn.aviatorEngine = function(options) {
        return this.each(function() {
            if (!$.data(this, 'aviator_engine')) {
                $.data(this, 'aviator_engine', new AviatorEngine(this, options));
            }
        });
    };
})(jQuery);


/* ====================================
   PERFORMANCE LOGIC & INITIALIZATION
======================================= */
// Corrected initialization using jQuery selector and plugin initialization hook
const $canvasElement = $('#gameCanvas').aviatorEngine();
const engine = $canvasElement.data('aviator_engine');

const aviator = document.querySelector(".aviator");
const windowWidth = window.innerWidth;

let loopInterval = null;
let flightStartTime = null;

function clearFlightInterval() {
    if (loopInterval) {
        clearInterval(loopInterval);
        loopInterval = null;
    }
}

function planeFly() {
    clearFlightInterval();

    flightStartTime = Date.now();
    loopInterval = setInterval(() => {
        const elapsed = Date.now() - flightStartTime;
        engine.updateFlight(elapsed);
    }, 30); 
}

// Core setup for socket connectivity
const socket = io("http://localhost:3000", {
    path: "/socket"
});

socket.on("connect", () => {
    socket.emit("join:room", {
        roomKey: "aviator",
        user_id: user_id,
    });
});

/* ====================================
   UTILITY HELPER FUNCTIONS
======================================= */
function animateCount(element, endValue, duration = 5000) {
    const startValue = 0;
    const incrementTime = 20; 
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

/* ====================================
   SOCKET REAL-TIME PIPELINES
======================================= */
socket.on('bet:update',(data) => {
    total_win = 0;
    $('.progress-bar').css('width', 0 + '%');
    $('#total-win').text('0.00');
    $('#payout-bet').text('0.00');
    const bets = data.bets;
    const container = $('.live-bet .runninge-bet-items');
    animateCount('#total_bet', data.total_bets, 4000);
    $('#payout-bet').data('total_bets',data.total_bets);
    animateCount('#payout-bet', data.total_bets, 4000);

    container.empty();

    let index = 0;
    const interval = setInterval(function () {
        if (index >= bets.length) {
            clearInterval(interval);
            return;
        }

        const bot = bets[index];
        const username = bot.bet_name.substring(0, 1) + '***' + bot.bet_name.substring(bot.bet_name.length - 1);

        container.append(`
            <div class="bet-list-item bet-autocashout" data-bet_amount="${bot.bet_amount}" data-cashout="${bot.cashout_point}">
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
    updatePreviousBets(data.view, data.privious_crash_point);
});

socket.on("round:new", (data) => {
    multiplierLabel.hide();
    multiplierText.hide();
    gameLoading.addClass('active');
    loadingBar.addClass('animate');
    $('#round_id').val(data.roundId);
    round_id = data.roundId;
    isFirstLoad = false;
    crashed = false;
    
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
    clearFlightInterval();
    betStatus = 'bet';

    $('.bet-control').each(function () {
        $(this).data('waiting','');
        $(this).find('.waiting').hide();
    });

     plane.css({
        top: '',
        bottom: '-14px',
        left: '-5px',
        transform: 'rotate(-5deg) scale(1)',
        opacity: '1'
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
    flightStartTime = data.elapsedTime;
    multiplierText.show();
    multiplierText.removeClass('small medium big');
    
    multiplier = parseFloat(data.multiplier);
    
    if (data.multiplier < 2) {
        multiplierText.addClass('small');
        $('.glow-circle').addClass('small');
    }
    else if (data.multiplier < 10) {
        multiplierText.addClass('medium');
        $('.glow-circle').addClass('medium');  
    }
    else{
        multiplierText.addClass('big');
        $('.glow-circle').addClass('big');
    }
    updateCashout();
    betAutoCashout();
    
    if(canAnimate){
        planeFly();
        canAnimate = false;
    }
});

const payoutsBlock = $(".payouts-block");

socket.on("round:crash", (data) => {
    resetBtn(); 
    clearFlightInterval() 
    $('.glow-circle').removeClass('small medium big'); 
    progress = data.progress; 
    speed = data.speed; 
    crashed = true; 
    canAnimate = true; 
    multiplierLabel.show(); 
    const payout = $("<div>").addClass("payout").text(data.multiplier.toFixed(2) + "x"); 

    if (data.multiplier < 2) { 
        payout.css("color", "#34b4ff"); 
        multiplierText.addClass('small'); 
    }
    else if (data.multiplier < 10) { 
        payout.css("color", "#913ef8"); 
    }
    else{
        payout.css("color", "#c017b4"); 
        multiplierText.addClass('big'); 
    }

    payoutsBlock.prepend(payout); 

    payoutsBlock.children().each(function(index) { 
        $(this).css({ transition: "transform 0.4s ease" }); 
    });

    if (payoutsBlock.children().length > 20) { 
        payoutsBlock.children().last().remove(); 
    }

    plane.css({
        transition: 'all 0.8s cubic-bezier(0.25, 1, 0.5, 1)',
        top: '-300px',
        left: ($(aviator).width() + 300) + 'px',
        transform: 'translate(-50%, -50%) rotate(-15deg) scale(0.7)',
        opacity: '0'
    });

    setTimeout(() => {
        plane.css('transition', 'none');
        plane.css({
            top: '',
            bottom: '-14px',
            left: '-5px',
            transform: 'rotate(-5deg) scale(1)',
            opacity: '1'
        });

        plane.css(
            'transition',
            'left 0.03s linear, top 0.03s linear, transform 0.03s linear'
        );

    }, 800);

    setTimeout(() => {
        engine.triggerCrash(data.multiplier);
    },80);
});