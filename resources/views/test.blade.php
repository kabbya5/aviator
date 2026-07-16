<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviator Game Engine Clone</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
        }

        body, html {
            width: 100%;
            height: 100%;
            background-color: #000;
            overflow: hidden;
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Main Stage Container */
        .game-stage {
            position: relative;
            width: 100%;
            max-width: 1200px;
            height: 100%;
            max-height: 600px;
            background-color: #0b0b0b;
            overflow: hidden;
            border: 2px solid #1a1a1a;
            border-radius: 8px;
        }

        /* SVG Sunburst Background */
        .sunburst-bg {
            position: absolute;
            bottom: -50%;
            left: -30%;
            width: 160%;
            height: 160%;
            z-index: 1;
            transform-origin: 30% 70%;
            animation: rotateSunburst 120s linear infinite;
            opacity: 0.6;
            pointer-events: none;
        }

        @keyframes rotateSunburst {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }

        /* Layer Canvas for Plane and Curve */
        #gameCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        /* UI Screen Layer */
        .screen-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            pointer-events: none;
        }

        .screen {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            width: 100%;
            height: 100%;
        }

        .screen.active {
            display: flex;
        }

        /* 1. Waiting Screen Styling */
        .partner-logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .ufc-logo {
            color: #d20a0a;
            font-size: 4rem;
            font-weight: 900;
            font-style: italic;
            letter-spacing: -2px;
        }

        .divider {
            width: 2px;
            height: 50px;
            background-color: #333;
        }

        .aviator-brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            color: #fff;
        }

        .aviator-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #e21a22;
            font-style: italic;
            line-height: 1;
        }

        .partner-sub {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .spribe-badge {
            background-color: #11261a;
            border: 1px solid #1c452c;
            border-radius: 6px;
            padding: 6px 14px;
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .spribe-badge .brand-name {
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .spribe-badge .status-text {
            background: #28a745;
            color: #fff;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
            margin: 3px 0;
            text-transform: uppercase;
        }

        .spribe-badge .year-text {
            color: #666;
            font-size: 0.6rem;
        }

        /* 2. Flying Screen Styling */
        .counter-display {
            font-size: 5.5rem;
            font-weight: 900;
            color: #fff;
            text-shadow: 0 4px 20px rgba(0,0,0,0.6);
            transition: color 0.3s;
        }

        /* 3. Flew Away Screen Styling */
        .flew-away-title {
            color: #ccc;
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .flew-away-counter {
            font-size: 5.5rem;
            font-weight: 900;
            color: #cb1b22;
            text-shadow: 0 4px 20px rgba(0,0,0,0.6);
        }

        /* Radial Overlay Gradient matching the changing canvas background */
        .gradient-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            mix-blend-mode: screen;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        /* Subtle Corner Widget (Simulating User Counter / Static Bottom Right Asset) */
        .bottom-right-widget {
            position: absolute;
            bottom: 15px;
            right: 15px;
            z-index: 4;
            display: flex;
            align-items: center;
            background: rgba(0,0,0,0.4);
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #222;
        }
        .widget-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 6px;
        }
        .widget-val {
            color: #aaa;
            font-size: 0.75rem;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <div class="game-stage">
        
        <!-- Sunburst Asset Vector Layer -->
        <svg class="sunburst-bg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
            <g fill="#141414">
                <!-- Generates a clean 32-blade radial ray backdrop -->
                <polygon points="50,50 -50,-150 -10,-150" />
                <polygon points="50,50 30,-150 70,-150" />
                <polygon points="50,50 110,-150 150,-150" />
                <polygon points="50,50 190,-150 230,-150" />
                <polygon points="50,50 250,-110 250,-70" />
                <polygon points="50,50 250,-30 250,10" />
                <polygon points="50,50 250,50 250,90" />
                <polygon points="50,50 250,130 250,170" />
                <polygon points="50,50 230,250 190,250" />
                <polygon points="50,50 150,250 110,250" />
                <polygon points="50,50 70,250 30,250" />
                <polygon points="50,50 -10,250 -50,250" />
                <polygon points="50,50 -150,230 -150,190" />
                <polygon points="50,50 -150,150 -150,110" />
                <polygon points="50,50 -150,70 -150,30" />
                <polygon points="50,50 -150,-10 -150,-50" />
                <polygon points="50,50 -110,-150 -70,-150" />
            </g>
        </svg>

        <!-- Ambient Glow Dynamic Mask -->
        <div class="gradient-overlay" id="ambientGlow"></div>

        <!-- Render Layer -->
        <canvas id="gameCanvas"></canvas>

        <!-- UI State Layers -->
        <div class="screen-layer">
            
            <!-- STATE: WAITING -->
            <div class="screen" id="screenWaiting">
                <div class="partner-logo-container">
                    <div class="ufc-logo">UFC</div>
                    <div class="divider"></div>
                    <div class="aviator-brand">
                        <span class="aviator-title">Aviator</span>
                    </div>
                </div>
                <div class="partner-sub">Official Partners</div>
                <div class="spribe-badge">
                    <span class="brand-name">SPRIBE</span>
                    <span class="status-text">Official Product</span>
                    <span class="year-text">Since 2019</span>
                </div>
            </div>

            <!-- STATE: FLYING -->
            <div class="screen" id="screenFlying">
                <div class="counter-display" id="liveMultiplier">1.00x</div>
            </div>

            <!-- STATE: FLEW AWAY -->
            <div class="screen" id="screenFlewAway">
                <div class="flew-away-title">Flew Away!</div>
                <div class="flew-away-counter" id="finalMultiplier">2.10x</div>
            </div>

        </div>

        <!-- System Status Bar Visual -->
        <div class="bottom-right-widget">
            <div class="widget-dot"></div>
            <div class="widget-val" id="playerCount">281</div>
        </div>

    </div>

    <!-- Dependencies: jQuery Core -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            const canvas = document.getElementById('gameCanvas');
            const ctx = canvas.getContext('2d');
            const $glow = $('#ambientGlow');
            
            // State tracking options
            const STATES = { WAITING: 'WAITING', FLYING: 'FLYING', FLEW_AWAY: 'FLEW_AWAY' };
            let currentState = STATES.WAITING;

            // Mathematical Curve & Multiplier variables
            let currentMultiplier = 1.00;
            let targetCrashValue = 23.00; 
            let animationProgress = 0; 
            let loopTimeline = 0;

            // Dimensions Setup
            function resizeCanvas() {
                canvas.width = $('.game-stage').outerWidth();
                canvas.height = $('.game-stage').outerHeight();
            }
            resizeCanvas();
            $(window).on('resize', resizeCanvas);

            // Plane Asset Generation via inline SVG String
            const planeSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="60" height="60">
                <path d="M54,32 L22,46 L10,48 L14,34 L2,32 L14,30 L10,16 L22,18 Z" fill="#e21a22"/>
                <path d="M54,32 L58,26 L58,38 Z" fill="#b01015"/>
                <circle cx="50" cy="32" r="3" fill="#fff"/>
                <line x1="58" y1="20" x2="58" y2="44" stroke="#e21a22" stroke-width="3"/>
            </svg>`;

            const planeImage = new Image();
            planeImage.src = 'data:image/svg+xml;base64,' + btoa(planeSVG);

            // State Manager Engine Switcher
            function switchState(targetState) {
                currentState = targetState;
                $('.screen').removeClass('active');

                if(currentState === STATES.WAITING) {
                    $('#screenWaiting').addClass('active');
                    $glow.css('opacity', 0);
                } 
                else if(currentState === STATES.FLYING) {
                    $('#screenFlying').addClass('active');
                } 
                else if(currentState === STATES.FLEW_AWAY) {
                    $('#screenFlewAway').addClass('active');
                    $('#finalMultiplier').text(currentMultiplier.toFixed(2) + 'x');
                    $glow.css('opacity', 0);
                }
            }

            // Central Game Matrix Loop
            function engineLoop() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                loopTimeline++;

                // Process Active Frame States
                if (currentState === STATES.WAITING) {
                    // Pre-flight static baseline drawing
                    drawFlightPlot(0);
                    
                    // Simple programmatic cycle loop logic for demonstration
                    if(loopTimeline > 200) { 
                        // Randomize target payout ceiling contextually
                        targetCrashValue = (Math.random() * 3.5 + 1.1); 
                        currentMultiplier = 1.00;
                        animationProgress = 0;
                        switchState(STATES.FLYING);
                    }
                    // Emulate passive variation of online telemetry numbers
                    if(loopTimeline % 30 === 0) {
                        $('#playerCount').text(Math.floor(Math.random() * 120 + 160));
                    }
                } 
                
                else if (currentState === STATES.FLYING) {
                    animationProgress += 0.0035; // Global Velocity Factor
                    
                    // Progressive exponential multiplier calculation
                    currentMultiplier = 40;
                    $('#liveMultiplier').text(currentMultiplier.toFixed(2) + 'x');

                    // Adjust structural styling based on multiplier thresholds
                    updateAmbientGlow(currentMultiplier);
                    
                    // Run Active Curve Plots
                    drawFlightPlot(animationProgress);

                    // Payout Cap Validation Condition
                    if (currentMultiplier >= targetCrashValue) {
                        switchState(STATES.FLEW_AWAY);
                        loopTimeline = 0;
                    }
                } 
                
                else if (currentState === STATES.FLEW_AWAY) {
                    // Retain static graph profile vector, fly plane off screen edge boundaries
                    animationProgress += 0.012; 
                    drawFlightPlot(animationProgress, true);

                    if(loopTimeline > 150) { 
                        loopTimeline = 0;
                        switchState(STATES.WAITING);
                    }
                }

                requestAnimationFrame(engineLoop);
            }

            // Quadratic Bezier Vector Geometry Tracing Renderer
            function drawFlightPlot(progress, hasFlewAway = false) {
                let startX = 0;
                let startY = canvas.height;
                
                // Normalizing graph limits against actual viewport container space boundaries
                let maxTargetX = canvas.width * 0.85;
                let maxTargetY = canvas.height * 0.25;

                let currentX, currentY;

                if (!hasFlewAway) {
                    // Bind progress factor safely inside constraints limit
                    let limitProgress = Math.min(progress, 1);
                    currentX = startX + (maxTargetX - startX) * limitProgress;
                    currentY = startY - (startY - maxTargetY) * Math.pow(limitProgress, 1.8);
                } else {
                    // Acceleration calculation vectors for final fly away state
                    let exitProgress = progress - 0.012; 
                    let lastX = startX + (maxTargetX - startX) * Math.min(exitProgress, 1);
                    let lastY = startY - (startY - maxTargetY) * Math.pow(Math.min(exitProgress, 1), 1.8);
                    
                    let escapeDelta = (progress - exitProgress) * 40;
                    currentX = lastX + (canvas.width * 0.4 * escapeDelta);
                    currentY = lastY - (canvas.height * 0.5 * escapeDelta);
                }

                // Control node coordinates logic for Bezier drawing computations
                let cpX = currentX * 0.6;
                let cpY = startY;

                if (progress > 0) {
                    // 1. Fill Red Ribbon Geometry Mesh underneath the curve
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                    ctx.quadraticCurveTo(cpX, cpY, currentX, currentY);
                    ctx.lineTo(currentX, startY);
                    ctx.closePath();

                    let areaGlow = ctx.createLinearGradient(0, currentY, 0, canvas.height);
                    areaGlow.addColorStop(0, 'rgba(226, 26, 34, 0.7)');
                    areaGlow.addColorStop(1, 'rgba(180, 16, 21, 0.0)');
                    ctx.fillStyle = areaGlow;
                    ctx.fill();

                    // 2. Stroke primary red arc border geometry line
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                    ctx.quadraticCurveTo(cpX, cpY, currentX, currentY);
                    ctx.strokeStyle = '#e21a22';
                    ctx.lineWidth = 4;
                    ctx.lineCap = 'round';
                    ctx.stroke();
                }

                // 3. Render tracking crosshair grid guides
                if (currentState === STATES.FLYING && progress > 0) {
                    ctx.strokeStyle = 'rgba(226, 26, 34, 0.4)';
                    ctx.lineWidth = 1;
                    ctx.setLineDash([4, 4]);
                    
                    // Vertical axis track marker
                    ctx.beginPath();
                    ctx.moveTo(currentX, currentY);
                    ctx.lineTo(currentX, canvas.height);
                    ctx.stroke();

                    // Reset line dashes back to solid standard structure
                    ctx.setLineDash([]);
                }

                // 4. Transform Matrix Calculations for the Plane Node Orientation
                if (planeImage.complete && (currentState === STATES.FLYING || (currentState === STATES.FLEW_AWAY && hasFlewAway))) {
                    ctx.save();
                    ctx.translate(currentX, currentY);
                    
                    // Dynamically calculate plane vector angle rotation based on trajectory delta updates
                    let deltaX = currentX - (startX + (maxTargetX - startX) * Math.max(0, progress - 0.01));
                    let deltaY = currentY - (startY - (startY - maxTargetY) * Math.pow(Math.max(0, progress - 0.01), 1.8));
                    let angle = Math.atan2(deltaY, deltaX);
                    
                    ctx.rotate(angle * 0.4); // Smooth damp angle trajectory profile
                    ctx.drawImage(planeImage, -50, -45, 60, 60);
                    ctx.restore();
                }
            }

            // Interactive Color Space Shift Map for ambient backdrop glow updates
            function updateAmbientGlow(multiplier) {
                if(multiplier < 1.5) {
                    $glow.css({
                        'opacity': 0.4,
                        'background': 'radial-gradient(circle at 60% 40%, rgba(0,90,140,0.4) 0%, rgba(0,0,0,0) 70%)'
                    });
                    $('#liveMultiplier').css('color', '#fff');
                } else if(multiplier >= 1.5 && multiplier < 2.5) {
                    $glow.css({
                        'opacity': 0.5,
                        'background': 'radial-gradient(circle at 70% 30%, rgba(90,20,120,0.4) 0%, rgba(0,0,0,0) 70%)'
                    });
                    $('#liveMultiplier').css('color', '#fff');
                } else {
                    $glow.css({
                        'opacity': 0.6,
                        'background': 'radial-gradient(circle at 80% 20%, rgba(140,10,30,0.4) 0%, rgba(0,0,0,0) 70%)'
                    });
                    // Dynamic alert pulse text state modification
                    if(Math.floor(loopTimeline / 10) % 2 === 0) {
                        $('#liveMultiplier').css('color', '#e21a22');
                    } else {
                        $('#liveMultiplier').css('color', '#fff');
                    }
                }
            }

            // Init Engine Startup Procedures
            switchState(STATES.WAITING);
            engineLoop();
        });
    </script>
</body>
</html>