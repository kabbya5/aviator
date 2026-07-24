<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviator Canvas Engine Demo</title>
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #0b0c0e;
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #ffffff;
            overflow: hidden;
        }

        .game-wrapper {
            width: 100%;
            background: #141518;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid #22252a;
            height: 100%;
        }

        .canvas-container {
            position: relative;
            width: 100%;
            height: 500px;
            background: rgb(21,21,21);
            /* Dark background sunburst simulation */
            background: radial-gradient(circle, rgba(29,29,29,1) 0%, rgba(10,10,10,1) 100%);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #1c1e22;
        }

        /* Sunburst lines decoration overlay */
        .canvas-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background-image: repeating-conic-gradient(
                from 0deg,
                rgba(255, 255, 255, 0.01) 0deg 15deg,
                transparent 15deg 30deg
            );
            pointer-events: none;
            z-index: 1;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        #plane {
            position: absolute;
            z-index: 10;
            display: none;
            width: 85px;
            height: auto;
            pointer-events: none;
            transform-origin: center center;
            will-change: left, top, transform;
            transition: transform 0.05s linear;
        }

        .controls {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        button {
            background-color: #2c3038;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #3d434f;
        }

        button:hover {
            background-color: #3d434f;
        }

        button.active-btn {
            background-color: #e20630;
            border-color: #ff2b52;
        }

        .status-badge {
            text-align: center;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888e9b;
        }
    </style>
</head>
<body>

    <div class="status-badge">Current State: <span id="state-text" style="color: #e20630; font-weight: bold;">WAITING</span></div>

    <div class="game-wrapper">
        <div class="canvas-container">
            <canvas id="aviatorGameCanvas"></canvas>
            <img id="plane" src="{{ asset('custom_aviator/img/animation-aviator.gif') }}" alt="Aviator Plane">
        </div>

        <!-- Simulated Server Control Hooks -->
        <div class="controls">
            <button id="btn-waiting" class="active-btn">1. Waiting Screen</button>
            <button id="btn-loading">2. Load (1.00x)</button>
            <button id="btn-start">3. Start Flight</button>
            <button id="btn-crash">4. Crash (Flew Away)</button>
        </div>
    </div>

    <script>
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
                    this.renderWaitingState();
                }

                resizeCanvas() {
                    const width = this.$canvas.parent().width();
                    const height = this.$canvas.parent().height();
                    this.$canvas.attr({ width, height });
                    
                    if (this.state === STATES.WAITING) this.renderWaitingState();
                    if (this.state === STATES.LOADING) this.startLoading();
                }

                renderWaitingState() {
                    this.state = STATES.WAITING;
                    this.maxReachedTime = null; // Reset track timestamp
                    this.prevPlaneX = 0;
                    this.prevPlaneY = 0;
                    this.$plane.hide(); // Hide custom flight image inside waiting space
                    
                    const w = this.$canvas.width();
                    const h = this.$canvas.height();
                    this.ctx.clearRect(0, 0, w, h);

                    // Replicating Partner Promo layout safely inside canvas view 
                    this.ctx.textAlign = 'center';
                    this.ctx.textBaseline = 'middle';
                    
                    this.ctx.font = `bold ${h * 0.09}px ${this.options.fontFamily}`;
                    this.ctx.fillStyle = '#E20630';
                    this.ctx.fillText('AVIATOR', w / 2, h / 2 - 20);

                    this.ctx.font = `600 ${h * 0.04}px ${this.options.fontFamily}`;
                    this.ctx.fillStyle = '#FFFFFF';
                    this.ctx.fillText('WAITING FOR NEXT ROUND', w / 2, h / 2 + 30);
                }

                startLoading() {
                    this.state = STATES.LOADING;
                    this.maxReachedTime = null; // Reset track timestamp
                    this.prevPlaneX = 0;
                    this.prevPlaneY = 0;
                    this.multiplier = 1.00;
                    this.$plane.hide(); // Keep image hidden during loading screens
                    this.renderStaticState("1.00x", "#FFFFFF");
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
                    this.drawDynamicGlow(canvasW, canvasH);
                    this.drawFlightPath();
                    this.drawMultiplierText(`${this.multiplier.toFixed(2)}x`, "#FFFFFF");
                }

                triggerCrash(finalMultiplier) {
                    this.state = STATES.CRASHED;
                    this.maxReachedTime = null; // Reset track timestamp
                    this.prevPlaneX = 0;
                    this.prevPlaneY = 0;
                    this.$plane.hide(); // Hide plane overlay upon cash out/crash

                    const w = this.$canvas.width();
                    const h = this.$canvas.height();
                    this.ctx.clearRect(0, 0, w, h);

                    // Render exact "FLEW AWAY!" display overlay text
                    this.drawMultiplierText(`${finalMultiplier.toFixed(2)}x`, "#E20630", "FLEW AWAY!");
                }

                drawDynamicGlow(w, h) {
                    // Shifting glow theme depending on high-multiplier states
                    let glowColor = "rgba(0, 162, 255, 0.09)"; // Flight path blue
                    if (this.multiplier >= 2.0) {
                        glowColor = "rgba(157, 23, 247, 0.18)"; // Target purple tracking hue
                    }

                    const gradient = this.ctx.createRadialGradient(
                        this.planeX, this.planeY, 5, 
                        this.planeX, this.planeY, Math.max(w, h) * 0.5
                    );
                    gradient.addColorStop(0, glowColor);
                    gradient.addColorStop(1, 'rgba(0,0,0,0)');
                    
                    this.ctx.fillStyle = gradient;
                    this.ctx.fillRect(0, 0, w, h);
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

                renderStaticState(text, color) {
                    const w = this.$canvas.width();
                    const h = this.$canvas.height();
                    this.ctx.clearRect(0, 0, w, h);
                    this.drawMultiplierText(text, color);
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


        /**
         * ----------------------------------------------------
         * APPLICATION CONTROLLER LOGIC
         * ----------------------------------------------------
         */
        $(document).ready(function() {
            // Instantiate plugin connection setup
            const $canvas = $('#aviatorGameCanvas').aviatorEngine();
            const engine = $canvas.data('aviator_engine');

            let loopInterval = null;
            let flightStartTime = null;
            let mockMultiplier = 1.00;

            function updateStateUI(stateName) {
                $('#state-text').text(stateName);
                $('.controls button').removeClass('active-btn');
            }

            function clearFlightInterval() {
                if (loopInterval) {
                    clearInterval(loopInterval);
                    loopInterval = null;
                }
            }

            // 1. Trigger Waiting Phase Display Hook
            $('#btn-waiting').on('click', function() {
                clearFlightInterval();
                updateStateUI('WAITING');
                $(this).addClass('active-btn');
                engine.renderWaitingState();
            });

            // 2. Trigger Pre-game Loading Phase Display Hook
            $('#btn-loading').on('click', function() {
                clearFlightInterval();
                updateStateUI('LOADING');
                $(this).addClass('active-btn');
                engine.startLoading();
            });

            // 3. Trigger Active Simulation Engine Loops
            $('#btn-start').on('click', function() {
                clearFlightInterval();
                updateStateUI('FLYING');
                $(this).addClass('active-btn');

                flightStartTime = Date.now();
                mockMultiplier = 1.00;

                // Loop frame cycle step simulation
                loopInterval = setInterval(() => {
                    const elapsed = Date.now() - flightStartTime;
                    
                    // Progressive math curve scaling matching typical game parameters (scaled faster for rapid climb!)
                    mockMultiplier += 0.005 * Math.pow(elapsed / 1000, 1.2);
                    
                    // Feed current dynamic status details down to engine
                    engine.updateFlight(mockMultiplier, elapsed);
                }, 30); // ~33 FPS smooth tracing loop refresh rate
            });

            // 4. Trigger Instant Round Crashed Termination Hook
            $('#btn-crash').on('click', function() {
                clearFlightInterval();
                updateStateUI('CRASHED');
                $(this).addClass('active-btn');
                
                // If it wasn't running, show a fixed mock value crash snapshot
                if(mockMultiplier === 1.00) mockMultiplier = 4.18; 
                
                engine.triggerCrash(mockMultiplier);
            });
        });
    </script>
</body>
</html>