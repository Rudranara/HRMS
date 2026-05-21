<?php
session_start();
require 'db_connection.php';

// Check if plan and amount are set and if the update has already been processed
if (!isset($_GET['plan']) || !isset($_GET['amount'])) {
    die("<div class='alert alert-danger animated bounceIn'>❌ Invalid request. Please try again.</div>");
}

// Prevent duplicate updates — only run this block once per payment
if (isset($_SESSION['subscription_updated']) && $_SESSION['subscription_updated'] === true) {
    die("<div class='alert alert-warning animated bounceIn'>⚠️ Subscription already updated. Please don’t refresh the page.</div>");

    die(" <div class='container'>
       
            <div class='success-message'>🎉 Subscription Updated!</div>
          
     
            <div class='error-message'>❌ Subscription already updated.</div>
            <p class='info'>Please contact support if the issue persists.</p>
     
        <a href='dashboard' class='btn'>Go to Dashboard</a>
    </div></div>");
}

$plan = $_GET['plan'];
$amount = $_GET['amount'];
$months = ($plan == 1) ? 1 : (($plan == 3) ? 3 : 14);

// Fetch current subscription expiry date
$admin_query = $conn->query("SELECT subscription_expire_date FROM admins LIMIT 1");
$admin = $admin_query->fetch_assoc();

$current_expiry = $admin['subscription_expire_date'];
$today = date('Y-m-d');

// Determine new expiry date
if (strtotime($current_expiry) > strtotime($today)) {
    // Current subscription still active — extend from expiry date
    $new_expiry = date('Y-m-d', strtotime("$current_expiry +$months months"));
} else {
    // Subscription expired or not set — start from today
    $new_expiry = date('Y-m-d', strtotime("+$months months"));
}

// Update subscription expiry date
$update_sub = $conn->query("UPDATE admins SET subscription_expire_date = '$new_expiry'");

if ($update_sub) {
    $_SESSION['subscription_updated'] = true; // Set session flag after successful update
   
} else {
    echo "<div class='alert alert-danger animated shake'>❌ Failed to update subscription. Please contact support.</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Success | Charchika</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 123, 255, 0.1);
            animation: fadeIn 1s ease-in-out;
        }
        .success-message {
            color: #28a745;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            animation: popIn 0.5s ease-in-out;
        }
        .error-message {
            color: #dc3545;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            animation: shake 0.5s ease-in-out;
        }
        .info {
            font-size: 18px;
            color: #555;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #0056b3;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes popIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($update_sub): ?>
            <div class="success-message">🎉 Subscription Updated!</div>
            <p class="info">Your subscription is now active until <strong><?= $new_expiry ?></strong>.</p>
            <p class="info">Thank you for choosing Maison Employee Management Software!</p>
        <?php else: ?>
            <div class="error-message">❌ Failed to update subscription.</div>
            <p class="info">Please contact support if the issue persists.</p>
        <?php endif; ?>
        <a href="dashboard" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>
