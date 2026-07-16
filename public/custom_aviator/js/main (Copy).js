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

    const STATES = {
        WAITING: 'waiting',
        LOADING: 'loading',
        FLYING: 'flying',
        CRASHED: 'crashed'
    };

    class AviatorEngine {
        constructor(element, options = {}) {
            this.$canvas = $(element);
            this.ctx = this.$canvas[0].getContext('2d');
            this.$plane = $('#plane'); // Custom DOM image selector
            
            this.options = $.extend({
                fontFamily: '"Arial", sans-serif',
                curveColor: '#E20630',
                fillColor: 'rgba(226, 6, 48, 0.25)',
                planeColor: '#E20630'
            }, options);

            this.state = STATES.WAITING;
            this.multiplier = 1.00;
            
            this.planeX = 0;
            this.planeY = 0;
            this.prevPlaneX = 0;
            this.prevPlaneY = 0;
            this.maxReachedTime = null; // To keep track of when max height/width was attained

            this.init();
        }

        init() {
            this.resizeCanvas();
            $(window).on('resize', () => this.resizeCanvas());
        }

        resizeCanvas() {
            const width = this.$canvas.parent().width();
            const height = this.$canvas.parent().height();
            this.$canvas.attr({ width, height });
            
        }

        updateFlight(currentMultiplier, elapsedMs) {
            this.state = STATES.FLYING;
            this.multiplier = parseFloat(currentMultiplier);
            
            // 1. Get the actual canvas dimensions
            const canvasW = this.$canvas.width();
            const canvasH = this.$canvas.height();

            // 2. Clear the ENTIRE canvas area to prevent trail artifacts
            this.ctx.clearRect(0, 0, canvasW, canvasH);

            // 3. Define the maximum bounding box boundaries for the path
            // We constrain base width to 75% to leave a safe 25% margin for the +20% width oscillation
            const w = canvasW * 0.75; 
            const h = canvasH * 0.80; 

            // Progress tracing (caps at 1.0). Flight time reduced to 8000ms for faster ascent!
            const maxLimit = 1.0;
            let progress = elapsedMs / 8000; 
            if (progress >= maxLimit) {
                progress = maxLimit;
                if (this.maxReachedTime === null) {
                    this.maxReachedTime = elapsedMs;
                }
            }

            let targetX = canvasW * 0.05 + (w - canvasW * 0.05) * progress;
            let targetY = canvasH * 0.85 - (canvasH * 0.85 - (canvasH - h)) * Math.pow(progress, 2); 

            if (this.maxReachedTime !== null) {
                const extendedTimeSec = (elapsedMs - this.maxReachedTime) / 1000;
                const cycleTime = extendedTimeSec % 4;

                const maxDeltaY = canvasH * 0.30; 
                const maxDeltaX = canvasW * 0.20; 

                if (cycleTime < 2) {
                    const factor = cycleTime / 2; 
                    targetY += maxDeltaY * factor;     
                    targetX += maxDeltaX * factor;     
                } else {
                    // Phase 2 (Next 2 Seconds): Increase height (decrease Y coordinate) and decrease width
                    const factor = (cycleTime - 2) / 2; 
                    targetY += maxDeltaY * (1 - factor); 
                    targetX += maxDeltaX * (1 - factor); 
                }
            }

            // Cache previous position to calculate movement angle
            if (this.prevPlaneX === 0) {
                this.prevPlaneX = targetX;
                this.prevPlaneY = targetY;
            }

            // Calculate rotation angle matching vector progress tangent slopes
            const deltaX = targetX - this.prevPlaneX;
            const deltaY = targetY - this.prevPlaneY;
            let angle = Math.atan2(deltaY, deltaX);

            // Force plane level straight at maximum limits to prevent jitter oscillations
            if (this.maxReachedTime !== null) {
                const cycleTime = ((elapsedMs - this.maxReachedTime) / 1000) % 4;
                if (cycleTime >= 1.9 && cycleTime <= 2.1) {
                    angle = 0;
                }
            }

            this.planeX = targetX;
            this.planeY = targetY;
            
            // Update trace parameters
            this.prevPlaneX = targetX;
            this.prevPlaneY = targetY;

            // 5. Apply dynamic positioning and rotation to the plane image element
            this.$plane.show().css({
                left: `${targetX}px`,
                top: `${targetY}px`,
                transform: `translate(-25%, -55%)`
            });

            // 6. Draw path and text elements
            this.drawFlightPath();
        }

     

        drawFlightPath() {
            const h = this.$canvas.height();
            const startX = this.$canvas.width() * 0.05;
            const startY = h * 0.85;
            
            this.ctx.beginPath();
            this.ctx.moveTo(startX, startY);
            this.ctx.quadraticCurveTo(
                this.planeX * 0.5 + startX * 0.8, startY, 
                this.planeX, this.planeY
            );
            
            this.ctx.strokeStyle = this.options.curveColor;
            this.ctx.lineWidth = 3;
            this.ctx.stroke();

            // Create background fill loop shape bounded perfectly underneath
            this.ctx.lineTo(this.planeX, startY);
            this.ctx.lineTo(startX, startY);
            this.ctx.fillStyle = this.options.fillColor;
            this.ctx.fill();
            this.ctx.closePath();
        }
           triggerCrash(finalMultiplier) {
            this.state = STATES.CRASHED;
            this.maxReachedTime = null; // Reset track timestamp
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
    betStatus = 'bet';
    // Dynamically update canvas screen text to match server clock countdown ticker

    $('.bet-control').each(function () {
        $(this).data('waiting','');
        $(this).find('.waiting').hide();
    });

     plane.css({
        top: '',
        bottom: '-14px',
        left: '-15px',
        transform: 'rotate(0deg) scale(1)',
        opacity: '1'
    });
});

socket.on("bet:close", () => {
    betStatus = 'running';
    gameLoading.removeClass('active');
    // Lock animations onto static 1.00x preparing mode
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
    
    // Pass real-time coordinates down into canvas layer update flight track loop
    
    engine.updateFlight(data.multiplier, data.elapsedTime);
});

const payoutsBlock = $(".payouts-block");

socket.on("round:crash", (data) => {
    resetBtn(); //[cite: 1]
    $('.glow-circle').removeClass('small medium big'); //[cite: 1]
    progress = data.progress; //[cite: 1]
    speed = data.speed; //[cite: 1]
    crashed = true; //[cite: 1]
    canAnimate = true; //[cite: 1]
    multiplierLabel.show(); //[cite: 1]
    const payout = $("<div>").addClass("payout").text(data.multiplier.toFixed(2) + "x"); //[cite: 1]

    if (data.multiplier < 2) { //[cite: 1]
        payout.css("color", "#34b4ff"); //[cite: 1]
        multiplierText.addClass('small'); //[cite: 1]
    }
    else if (data.multiplier < 10) { //[cite: 1]
        payout.css("color", "#913ef8"); //[cite: 1]
    }
    else{
        payout.css("color", "#c017b4"); //[cite: 1]
        multiplierText.addClass('big'); //[cite: 1]
    }

    payoutsBlock.prepend(payout); //[cite: 1]

    payoutsBlock.children().each(function(index) { //[cite: 1]
        $(this).css({ transition: "transform 0.4s ease" }); //[cite: 1]
    });

    if (payoutsBlock.children().length > 20) { //[cite: 1]
        payoutsBlock.children().last().remove(); //[cite: 1]
    }

    plane.css({
        transition: 'all 0.8s cubic-bezier(0.25, 1, 0.5, 1)',
        top: '-300px',
        left: ($(aviator).width() + 300) + 'px',
        transform: 'translate(-50%, -50%) scale(0.7)',
        opacity: '0'
    });

    setTimeout(() => {
        plane.css('transition', 'none');
        plane.css({
            top: '',
            bottom: '-14px',
            left: '-15px',
            transform: 'rotate(0deg) scale(1)',
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