<?php
session_start();
require 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index");
    exit;
}
// Dynamic page detection
$current_page = basename($_SERVER['PHP_SELF']); // Gets the current page name
// Admin details (replace with actual session variable names storing admin details)
$admin_id = $_SESSION['admin_id'] ?? '123';
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$employee_id = $_SESSION['admin_employee_id'] ?? '00000';
$admin_roll = $_SESSION['admin_roll'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? 'Not Set';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans - Employee Management Software</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link id="pagestyle" href="assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
    <style>
        @charset "UTF-8";
        * {
            box-sizing: border-box;
        }
        body {
 margin-top: 100px;
    padding: 0;
    height: 100vh;
    background: #000; /* Night sky */

    justify-content: center;
    align-items: center;
    position: relative;
}

.star {
    position: absolute;
    width: 10px;
    height: 10px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 0 8px #fff;
    animation: blink 2s infinite ease-in-out;
}

.star:nth-child(1) {
    top: 20%;
    left: 30%;
    animation-delay: 0.5s;
}

.star:nth-child(2) {
    top: 50%;
    left: 70%;
    animation-delay: 1s;
}

.star:nth-child(3) {
    top: 80%;
    left: 40%;
    animation-delay: 1.5s;
}

.star:nth-child(4) {
    top: 60%;
    left: 20%;
    animation-delay: 2s;
}

.star:nth-child(5) {
    top: 10%;
    left: 80%;
    animation-delay: 2.5s;
}

@keyframes blink {
    0%, 100% {
        opacity: 0;
        transform: scale(0.8);
    }
    50% {
        opacity: 1;
        transform: scale(1.2);
    }
}

        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .header h1 {
            font-size: 2.5rem;
            color:rgb(224, 100, 87);
        }
        .header p {
            font-size: 1.1rem;
            color:rgb(245, 244, 243);
        }
        .container {
            margin: 0 auto;
            text-align: center;
            white-space: nowrap;
        }
        .card {
            display: inline-block;
            position: relative;
            background: #EDDDD4;
            color: #283D3B;
            width: 349px;
            height: 450px;
            border-radius: 20px;
            overflow: hidden;
            margin: 0 10px;
            text-align: center;
            box-shadow:  #FFFFFF;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow .3s ease;
        }
        .card:hover, .card.selected {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(0, 123, 255, 0.3);
            border: 4px solid #007bff;
        }
        .card h2 {
            margin: 0;
            width: 100%;
            font-size: 30px;
            background: #C44536;
            padding: 20px 0;
            color: #EDDDD4;
        }
        .card h3 {
            margin: 20px 0;
            font-size: 50px;
            text-shadow: 3px 2px 2px #283d3b38;
        }
        .card h3 span {
            font-size: 15px;
        }
        .card h2 span {
            font-size: 15px;
        }
        .card p {
            font-style: italic;
            margin: 0 0 30px 0;
        }
        .card ul {
            text-align: left;
            padding: 0 50px;
            margin: 0;
        }
        .card ul li {
            display: block;
        }
        .card ul li:not(:last-child) {
            margin-bottom: 10px;
        }
        .card ul li.aval::before {
            content: "✅";
            font-size: 20px;
            color: #197278;
            width: 40px;
            display: inline-block;
        }
        .card ul li.unaval::before {
            content: "❌";
            font-size: 20px;
            color: #C44536;
            width: 40px;
            display: inline-block;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
        }
        .promo-success {
            display: none;
            color: #28a745;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        .promo-fail {
            color: #C44536;
            font-weight: bold;
            display: none;
        }

        .hot-badge::after {
            content: "HOT";
            position: absolute;
            background: linear-gradient(to right, #ffd400, #ffbc00);
            padding: 5px 54px;
            box-shadow: 0 0 5px 3px #715e006e;
            top: 17px;
            right: -46px;
            color: #5d4d00;
            transform: rotateZ(45deg);
        }
        .animated-message {
        font-size: 18px;
        font-weight: bold;
        color:rgb(207, 126, 117);
        animation: fadeIn 1.5s infinite;
        cursor: pointer;
    }

    @keyframes fadeIn {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }

    .promo-success {
        display: none;
        color: #28a745;
        font-size: 20px;
        font-weight: bold;
        margin-top: 10px;
        animation: fadeIn 1s ease-in-out;
    }

    .promo-fail {
        color: #C44536;
        font-weight: bold;
        display: none;
    }

    @keyframes fadeIn {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }
    h5{
      color:rgb(224, 100, 87);
            font-weight: bold;
    }
    h4{
      color:rgb(224, 100, 87);
            font-weight: bold;
    }
    .btn-success {
    background-color: #C44536;
    border-color: #22c55e;
}
    </style>
</head>
<body>
<div class="star"></div>
<div class="star"></div>
<div class="star"></div>
<div class="star"></div>
<div class="star"></div>
<?php
// Get admin subscription details
$admin_id = $_SESSION['admin_id'] ?? null;
$expired = false;
$show_reminder_modal = false;
$reminder_message = '';

if ($admin_id) {
    $check_sub = $conn->prepare("SELECT subscription_expire_date FROM admins WHERE id = ?");
    $check_sub->bind_param("i", $admin_id);
    $check_sub->execute();
    $sub_result = $check_sub->get_result();
    $sub_data = $sub_result->fetch_assoc();

    $today = date('Y-m-d');
    $expire_date = $sub_data['subscription_expire_date'] ?? null;

    // Check if subscription expired
    if (!$expire_date || $expire_date < $today) {
        $expired = true;
    } else {
        // Calculate days left until expiration
        $days_left = (strtotime($expire_date) - strtotime($today)) / (60 * 60 * 24);

        // Set specific reminder messages
        if ($days_left == 1) {
            $reminder_message = '⏳ Your subscription will expire tomorrow. Please renew to avoid service interruption.';
            $show_reminder_modal = !isset($_SESSION['reminder_shown']);
        } elseif ($days_left == 0) {
            $reminder_message = '⚠️ Your subscription will expire today. Please renew now to avoid losing access.';
            $show_reminder_modal = !isset($_SESSION['reminder_shown']);
        } elseif ($days_left <= 10 && $days_left > 1) {
            $reminder_message = "🔔 Your subscription will expire in $days_left days. Please renew soon.";
            $show_reminder_modal = !isset($_SESSION['reminder_shown']);
        }
    }
}
?>
<!-- Expired Alert -->
<?php if ($expired): ?>
    <div class="alert alert-danger text-center">
        <h5>🚨 Subscription Expired</h5>
        <p>Your subscription has expired. Please renew to continue accessing the dashboard.</p>
    </div>
<?php else: ?>
    <!-- Subscription Expiry Date Display -->
    <div class="text-center">
        <h5>📅 Subscription Expiry Date</h5>
        <p style="color: #ffffff;">Your subscription is active and will expire on: <strong><?= date("d M Y", strtotime($expire_date)) ?></strong></p>
    </div>
<?php endif; ?>

<!-- Reminder Alert (Shows only once before expiration) -->
<?php if ($show_reminder_modal && !$expired): ?>
    <div class="alert alert-warning alert-dismissible fade show text-center" role="alert">
        <strong><?= $reminder_message ?></strong>
    </div>
    <?php $_SESSION['reminder_shown'] = true; ?>
<?php endif; ?>

<!-- Header Section -->
<div class="header">
    <h1>Choose a Plan for Your Employee Management Software</h1>
    <p>Streamline your workforce operations with real-time tracking, seamless employee data management, and powerful admin tools.</p>
</div>

<!-- Plan Cards -->
<div class="container">
    <div class="card card-1" onclick="selectPlan(2500, '1')">
        <h2>Monthly</h2>
        <h3>₹2500<span>/(incl. GST)</span></h3>
        <p>Perfect for growing Businesses</p>
        <ul>
            <li class="aval">20GB storage</li>
            <li class="aval">100 Employee</li>
            <li class="aval">Multiple Admin</li>
            <li class="unaval">24/7 support</li>
        </ul>
    </div>
    <div class="card card-2 hot-badge" onclick="selectPlan(30000, '14')">
        <h2>Yearly<span>+2 Month Free</span></h2>
        <h3>₹30000<span>/(incl. GST)</span></h3>
        <p>Great for Large Businesses</p>
        <ul>
            <li class="aval">200GB storage</li>
            <li class="aval">Unlimited Employee</li>
            <li class="aval">Multiple Admin</li>
            <li class="aval">24/7 support</li>
        </ul>
    </div>
</div>

<!-- Pricing Breakdown Section -->
<div class="text-center mt-2">
    <h4>Pricing Breakdown</h4>
    <p style="color:#FFFFFF">Plan Price (incl. GST): ₹<span id="basePrice">0</span></p>
    <h4 class="total-amount">Final Total: ₹<span id="totalAmount">0</span></h4>
</div>

<!-- Proceed Button -->
<form action="payment_page" method="POST" class="text-center">
    <input type="hidden" name="plan" id="selectedPlan">
    <input type="hidden" name="amount" id="finalAmount">
    <button type="submit" class="btn btn-primary mt-3" id="proceedBtn" disabled>Proceed to Pay</button>
</form>

<script>
    let selectedPrice = 0;

    function selectPlan(price, plan) {
        document.querySelectorAll('.card').forEach(card => card.classList.remove('selected'));
        event.currentTarget.classList.add('selected');

        selectedPrice = price;
        document.getElementById('selectedPlan').value = plan;
        calculateTotal();
        document.getElementById('proceedBtn').disabled = false;
    }

    function calculateTotal() {
        if (selectedPrice === 0) return;
        document.getElementById('basePrice').innerText = selectedPrice.toFixed(2);
        document.getElementById('totalAmount').innerText = selectedPrice.toFixed(2);
        document.getElementById('finalAmount').value = selectedPrice.toFixed(2);
    }
</script>

</body>
</html>
