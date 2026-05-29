<?php
session_start();
require_once('../class/database.php');
$db = new database();

$dentists = $db->viewDentists();

/* AJAX fee request */
if (isset($_GET['get_fee'])) {
    $fee = $db->getActiveDentistFee($_GET['dentist_id']);
    echo $fee;
    exit();
}

/* Save payment */
if (isset($_POST['pay'])) {
    $patient_name = $_POST['patient_name'] ?? '';
    $dentist_id = $_POST['dentist_id'] ?? '';
    $amount = $_POST['amount'] ?? '';

    /* VALIDATION */
    if ($amount === "" || $amount == 0) {
        echo "<script>alert('No active consultation fee for this dentist!');</script>";
    } else {
        $db->savePayment($patient_name, $dentist_id, $amount);
        echo "<script>alert('Payment Saved Successfully!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; padding: 30px; }
        .box { width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; }
        button { width: 100%; padding: 10px; background: green; color: white; border: none; }
    </style>
</head>
<body>
<div class="box">
    <h2>Payment Form</h2>
    <form method="POST">
        <input type="text" name="patient_name" placeholder="Patient Name" required>
        <select name="dentist_id" id="dentistSelect" required>
            <option value="">Select Dentist</option>
            <?php foreach ($dentists as $d): ?>
                <option value="<?= htmlspecialchars($d['Dentist_ID']); ?>"><?= htmlspecialchars($d['Dentist_FN'] . ' ' . $d['Dentist_LN']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="amount" id="feeBox" readonly placeholder="Consultation Fee">
        <button type="submit" name="pay">Pay</button>
    </form>
</div>
<script>
document.getElementById('dentistSelect').addEventListener('change', function () {
    var id = this.value;
    if (!id) { document.getElementById('feeBox').value = ''; return; }
    fetch('payment.php?get_fee=1&dentist_id=' + encodeURIComponent(id))
        .then(function (res) { return res.text(); })
        .then(function (data) { document.getElementById('feeBox').value = (data === '' || data === '0') ? '0' : data; });
});
</script>
</body>
</html>
