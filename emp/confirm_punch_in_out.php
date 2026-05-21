<?php
// confirm_punch_in_out.php
require 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'];
    $employee_id = $_GET['employee_id'];
    $latitude = $_GET['latitude'];
    $longitude = $_GET['longitude'];
    $selfie_data = $_GET['selfie_data'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation Page</title>
</head>
<body>
    <h2>Confirmation for <?php echo $action === 'punch_in' ? 'Punch-In' : 'Punch-Out'; ?></h2>
    <p>You are trying to <?php echo $action === 'punch_in' ? 'punch in late' : 'punch out early'; ?>. Are you sure?</p>
    
    <form id="confirmationForm" action="process_punch_in_out" method="POST">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
        <input type="hidden" name="latitude" value="<?php echo $latitude; ?>">
        <input type="hidden" name="longitude" value="<?php echo $longitude; ?>">
        <input type="hidden" name="selfie_data" value="<?php echo $selfie_data; ?>">

        <button type="submit" name="confirm" value="yes">Yes</button>
        <a href="add_attendance">No, Go Back</a>
    </form>
</body>
</html>
