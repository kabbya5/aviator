<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Aviator</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    overflow:hidden;
    background:#0b1020;
    font-family:Arial;
}

/* canvas */
canvas{
    display:block;
}

/* multiplier */
.multiplier{
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    font-size:90px;
    font-weight:bold;
    color:rgba(255,255,255,0.15);
    pointer-events:none;
    user-select:none;
    z-index:10;
}

/* plane gif */
#plane{
    position:absolute;
    width:120px;
    height:auto;
    pointer-events:none;
    transform-origin:center center;
    z-index:20;

    /* smoother movement */
    transition:
        left 0.03s linear,
        top 0.03s linear,
        transform 0.03s linear;
}
</style>
</head>

<body>

<div
    class="multiplier"
    id="multiplier"
>
    1.00x
</div>

<!-- animated plane -->
<img
    id="plane"
    src="{{ asset('custom_aviator/img/animation-aviator.gif') }}"
>

<canvas id="gameCanvas"></canvas>

<script>
const canvas =
    document.getElementById("gameCanvas");

const ctx =
    canvas.getContext("2d");

const plane =
    document.getElementById("plane");

const multiplierText = document.getElementById("multiplier");

/* =========================
   CANVAS SIZE
========================= */

function resizeCanvas(){

    canvas.width =
        window.innerWidth;

    canvas.height =
        window.innerHeight;
}

resizeCanvas();

window.addEventListener(
    "resize",
    resizeCanvas
);

/* =========================
   GAME VARIABLES
========================= */

let progress = 0;

let speed = 0.002;

let multiplier = 1;

let crashed = false;

/* =========================
   BACKGROUND
========================= */

function drawBackground(){

    // dark gradient background
    const bg = ctx.createRadialGradient(
        canvas.width / 2,
        canvas.height / 2,
        100,
        canvas.width / 2,
        canvas.height / 2,
        canvas.width
    );

    bg.addColorStop(0, "#18233f");
    bg.addColorStop(1, "#060b16");

    ctx.fillStyle = bg;

    ctx.fillRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

    /* grid lines */

    ctx.strokeStyle =
        "rgba(255,255,255,0.04)";

    ctx.lineWidth = 1;

    for(let i=0;i<canvas.width;i+=80){

        ctx.beginPath();

        ctx.moveTo(i,0);

        ctx.lineTo(i,canvas.height);

        ctx.stroke();
    }

    for(let i=0;i<canvas.height;i+=80){

        ctx.beginPath();

        ctx.moveTo(0,i);

        ctx.lineTo(canvas.width,i);

        ctx.stroke();
    }
}

/* =========================
   CURVE
========================= */

var maxWidth = canvas.width * 0.95;

const topLimit = canvas.height * 0.09;

function drawCurve(){

    let endX = progress * canvas.width;



    if(endX > maxWidth){

        endX = maxWidth;
    }


    const curvePower = 2.35;

    const curveHeight = 0.99;

    /* filled area */

    ctx.beginPath();

    ctx.moveTo(
        0,
        canvas.height
    );

    for(let x=0;x<=endX;x++){

        const t =
            x / canvas.width;

        let y =
            canvas.height -
            Math.pow(t, curvePower) *
            canvas.height *
            curveHeight;

        // keep inside screen


        if (y <= topLimit) {

        const floatAmplitude = 12;
        const floatSpeed = 0.003;

        y =
            topLimit +
            Math.sin(Date.now() * floatSpeed) *
            floatAmplitude;
    }

        ctx.lineTo(x,y);
    }

    ctx.lineTo(
        endX,
        canvas.height
    );

    ctx.closePath();

    /* gradient fill */

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

    /* outline */

    ctx.beginPath();

    for(let x=0;x<= endX;x++){

        const t =
            x / canvas.width;

        let y =
            canvas.height -
            Math.pow(t, curvePower) *
            canvas.height *
            curveHeight;


        if (y <= topLimit) {

            const floatAmplitude = 12; // up/down height
            const floatSpeed = 0.003; // animation speed

            y = topLimit - floatSpeed * floatAmplitude;
        }

        if(x===0){

            ctx.moveTo(x,y);

        }else{

            ctx.lineTo(x,y);
        }
    }

    /* glow line */

    ctx.strokeStyle =
        "#ff174f";

    ctx.lineWidth = 4;

    ctx.shadowColor =
        "#ff174f";

    ctx.shadowBlur = 20;

    ctx.stroke();

    ctx.shadowBlur = 0;
}

/* =========================
   PLANE POSITION
========================= */

function getPlanePosition(){

    let x = progress * canvas.width;


    if (x > maxWidth) {
        x = maxWidth;
    }

    const t = x / canvas.width;

    let y =
        canvas.height -
        Math.pow(t,2.35) *
        canvas.height *
        0.99;


    if (y <= topLimit) {

        const floatAmplitude = 12;
        const floatSpeed = 0.003;

        y =
            topLimit +
            Math.sin(Date.now() * floatSpeed) *
            floatAmplitude;
    }

    return {x,y};
}

/* =========================
   ANIMATION
========================= */

function animate(){

    if(crashed){
        return;
    }

    drawBackground();

    drawCurve();

    const pos = getPlanePosition();

    /* plane movement */

    plane.style.left = (pos.x - 38) + "px";

    plane.style.top = (pos.y - 45) + "px";

    /* dynamic rotation */

    const angle =  -10 - (progress * 18);

    plane.style.transform = `rotate(${angle}deg)`;

    speed += 0.000002;

    progress += speed;

    /* multiplier */

    multiplier += (
        speed * 1.8
    );

    multiplierText.innerHTML =
        multiplier.toFixed(2) + "x";

    /* crash */

    if(progress >= 4){

        crashed = true;

        /* plane fly away */

        plane.style.transition =
            "all 0.8s ease";

        plane.style.top =
            "-300px";

        plane.style.left =
            (canvas.width + 300) + "px";

        plane.style.transform =
            "rotate(-45deg) scale(0.5)";

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

    plane.style.transition =
        "left 0.03s linear, top 0.03s linear, transform 0.03s linear";

    animate();
}

/* =========================
   START
========================= */

animate();

</script>

</body>
</html>
