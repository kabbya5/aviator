<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<title>Flying Plane Curve</title>

<style>

body{
    margin:0;
    overflow:hidden;
    background:#050816;
}

canvas{
    display:block;
}

#plane{
    position:absolute;
    width:120px;
    height:auto;
    pointer-events:none;
    transform-origin:center center;
    z-index:20;

    transition:
        left 0.03s linear,
        top 0.03s linear,
        transform 0.03s linear;
}

</style>
</head>
<body>

<canvas id="canvas"></canvas>
<img id="plane" src="{{ asset('custom_aviator/img/animation-aviator.gif') }}">

<script>
const plane = document.getElementById("plane");
const canvas = document.getElementById("canvas");

const ctx = canvas.getContext("2d");

/* =========================
   RESIZE
========================= */

function resizeCanvas(){
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}

resizeCanvas();

window.addEventListener(
    "resize",
    resizeCanvas
);

/* =========================
   SETTINGS
========================= */

let progress = 0;

const maxWidth = window.innerWidth * 0.85; // 8.5

const topLimit = canvas.height * 0.15;

/* =========================
   CURVE Y
========================= */

function getCurveY(x){

    const curvePower = 1.6;
    const curveHeight = 0.99;

    const t =
        x / canvas.width;

    let y =
        canvas.height -
        Math.pow(t, curvePower) *
        canvas.height *
        curveHeight;

    /*
      whole curve floating
      from 0 -> maxWidth
    */

    const floatAmplitude = 30;
    const floatSpeed = 0.002;

    /*
      stronger movement
      near the end
    */

    const influence = x / maxWidth;

    y +=
        Math.sin(
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

    let endX =
        progress * canvas.width;

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
   ANIMATION
========================= */

function animate(){

    requestAnimationFrame(
        animate
    );

    if(progress < 1.2){

        progress += 0.0025;
    }

    drawCurve();
}

animate();

</script>

</body>
</html>
