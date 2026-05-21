<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Aircraft Banner</title>
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      background-image: url(""); /* Optional background image */
      background-size: 200vw 100%;
      animation: parallax 60s linear infinite alternate;
      overflow: hidden;
      background: linear-gradient(#87ceeb, #ffffff);
    }

    @keyframes parallax {
      0% { background-position: 0 0; }
      100% { background-position: -100vw 0; }
    }

    #flight-group {
      position: absolute;
      display: flex;
      align-items: center;
      animation: flyIn 5s ease-out forwards;
      left: -1000px; /* start off-screen */
      top: 100px;
    }

    @keyframes flyIn {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(1200px); /* center position */
      }
    }

    #aircraft {
      width: 200px;
      aspect-ratio: 1/1;
      background-size: contain;
      background-repeat: no-repeat;
      background-image: url(""); /* Your aircraft image */
      position: relative;
      z-index: 1;
    }

    #aircraft::before,
    #aircraft::after {
      content: "";
      position: absolute;
      top: 70px;
      left: -140px;
      width: 170px;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgb(66, 66, 66), transparent);
      box-shadow: 0 0 10px white;
    }

    #aircraft::before {
      transform: translateY(-12px) rotate(4deg);
      animation: waveline1 2s infinite ease-in-out;
    }

    #aircraft::after {
      transform: translateY(13px) rotate(-4deg);
      animation: waveline2 2s infinite ease-in-out;
    }

    @keyframes waveline1 {
      0%, 100% { transform: translateY(-10px) rotate(4deg); }
      50% { transform: translateY(-11px) rotate(5deg); }
    }

    @keyframes waveline2 {
      0%, 100% { transform: translateY(13px) rotate(-4deg); }
      50% { transform: translateY(14px) rotate(-5deg); }
    }

    #banner {
      display: flex;
      justify-content: space-between;
      perspective: 1000px;
      transform: scale(0.5) translate(-240px, 50px);
      animation: moveUpDown 30s infinite ease-in-out;
    }

    @keyframes moveUpDown {
      0% {
        transform: scale(0.5) translate(-240px, 50px) perspective(1000px) rotateY(-10deg);
      }
      25% {
        transform: scale(0.5) translate(-250px, 40px) perspective(1000px) rotateY(-15deg);
      }
      50% {
        transform: scale(0.5) translate(-260px, 30px);
      }
      75% {
        transform: scale(0.5) translate(-250px, 40px) perspective(1000px) rotateY(-15deg);
      }
      100% {
        transform: scale(0.5) translate(-240px, 50px) perspective(1000px) rotateY(-10deg);
      }
    }

    .segment {
      width: 20px;
      height: 90px;
      margin-left: -1px;
      animation: wave 2s infinite ease-in-out;
      transform-origin: center;
      background-attachment: fixed;
      box-shadow: 0 0 10px white;
    }

    @keyframes wave {
      0%, 100% { transform: rotateX(0deg) translateY(0); }
      50% { transform: rotateX(0deg) translateY(7px); }
    }

    #propeller {
      position: absolute;
      display: block;
      z-index: 10;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, #ffffff00 0%, #9e9e9e 50%);
      border-radius: 50%;
      opacity: 0.5;
      transform: perspective(1000px) rotateY(10deg) translate(645px, -25px) scale(0.2);
    }

    #propeller::before {
      content: " ";
      position: absolute;
      display: block;
      width: 200px;
      height: 200px;
      background: linear-gradient(rgba(255, 255, 255, 0.567), rgba(255, 255, 255, 0) 40%);
      border-radius: 50%;
      animation: rotate 0.1s linear infinite;
    }

    @keyframes rotate {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div id="flight-group">

    <div id="propeller"></div>
    <div id="banner"></div>
  </div>

  <script>
    var currentYear = new Date().getFullYear();
    var yearsPassed = currentYear - 1980;
    // Create canvas
    var canvas = document.createElement("canvas");
    var ctx = canvas.getContext("2d");
    canvas.width = 800;
    canvas.height = 400;
    canvas.style.width = "800px";
    canvas.style.height = "200px";
    // Draw background
    ctx.fillStyle = "white";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw dynamic text
    ctx.font = "38px sans-serif";
    ctx.textBaseline = "middle";
    ctx.fillStyle = "#25b18e";
    ctx.fillText("  " + yearsPassed + " years", 10, 50);
    ctx.fillStyle = "#515dd2";
    ctx.fillText("of good vybes 💙 🥳 💻", 10 + ctx.measureText(yearsPassed + " years   ").width, 50);
    // Convert to image
    var img = new Image();
    img.src = canvas.toDataURL();

    img.onload = function () {
      var banner = document.getElementById("banner");
      for (var i = 0; i < 34; i++) {
        var segment = document.createElement("div");
        segment.className = "segment";
        segment.style.animationDelay = i * 0.1 + "s";
        segment.style.backgroundImage = "url(" + img.src + ")";
        segment.style.backgroundPositionX = -19 * i + "px";
        banner.appendChild(segment);
      }
    };
  </script>
</body>
</html>
