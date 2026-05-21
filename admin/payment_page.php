<?php
session_start();
require 'db_connection.php';

// Get plan and amount from form submission
$plan = $_POST['plan'];
$amount = $_POST['amount']; // Get the final calculated amount including discount

// Validate inputs
if (empty($plan) || empty($amount)) {
    die('Invalid request. Please select a plan.');
}

// Razorpay API Integration
require 'vendor/autoload.php';
use Razorpay\Api\Api;

$keyId = 'rzp_live_601GysqbInxlJ0';
$keySecret = 'mliEwT13ZjtDZ5Q6ijhB7Btu';

$api = new Api($keyId, $keySecret);

// Razorpay works in paise, so multiply amount by 100
$amountInPaise = intval($amount * 100); // Ensure it's an integer


try {
    // Create Razorpay order
    $order = $api->order->create([
        'receipt' => 'order_rcptid_' . time(),
        'amount' => $amountInPaise, // Amount in paise
        'currency' => 'INR',
        'payment_capture' => 1
    ]);

    // Store order details in session
    $_SESSION['order_id'] = $order->id;
    $_SESSION['plan'] = $plan;
    $_SESSION['amount'] = $amount;
} catch (Exception $e) {
    die('Error creating Razorpay order: ' . $e->getMessage());
}
?>

<!-- Razorpay Payment Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        "key": "<?= $keyId ?>",
        "amount": "<?= $amountInPaise ?>",
        "currency": "INR",
        "name": "My Attendance",
        "description": "Subscription Payment - <?= htmlspecialchars($plan) ?> Plan",
        "order_id": "<?= $order->id ?>",
        "handler": function (response) {
            window.location.href = `payment_success?plan=<?= urlencode($plan) ?>&amount=<?= $amount ?>`;
        },
        "prefill": {
            "name": "<?= $_SESSION['admin_name'] ?? 'Customer' ?>",
            "email": "<?= $_SESSION['admin_email'] ?? 'customer@example.com' ?>",
            "contact": "<?= $_SESSION['admin_phone'] ?? '' ?>"
        },
        "theme": {
            "color": "#007bff"
        }
    };

    var rzp = new Razorpay(options);
    rzp.open();
</script>
