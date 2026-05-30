<?php
session_start();
require_once('../class/database.php');
$db = new database();

$dentists = $db->viewDentists();

if (isset($_POST['save_fee'])) {

    $dentist_id = $_POST['dentist_id'];
    $rates = $_POST['fee'];
    $from = $_POST['valid_from'];
    $to = $_POST['valid_to'];
    $action = $_POST['action'];

    if ($action == "add") {
        $db->addDentistFee($dentist_id, $rates, $from, $to);
        echo "<script>alert('Dentist Fee Added Successfully!');</script>";
    } else {
        $db->updateDentistFee($dentist_id, $rates, $from, $to);
        echo "<script>alert('Dentist Fee Updated Successfully!');</script>";
    }
}

/* AJAX fee request */
if (isset($_GET['get_fee'])) {

    $fee = $db->getActiveDentistFee($_GET['dentist_id']);
    echo $fee;
    exit();
}

/* Save payment */
if (isset($_POST['pay'])) {

    $patient_name = trim($_POST['patient_name']);
    $dentist_id = $_POST['dentist_id'];
    $amount = $_POST['amount'];

    if ($amount == "" || $amount == 0) {
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
    <title>Payment</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f7fb;
            padding: 30px;
        }

        .box {
            width: 400px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
        }

        button {
            width: 100%;
            padding: 10px;
            background: green;
            color: white;
            border: none;
        }
    </style>
</head>

<body>

<div class="box">

<h2>Manage Dentist Fee</h2>

<form method="POST">

    <select name="dentist_id" required>
        <option value="">Select Dentist</option>
        <?php foreach ($dentists as $d) { ?>
            <option value="<?= $d['Dentist_ID']; ?>">
                <?= $d['Dentist_FN'] . ' ' . $d['Dentist_LN']; ?>
            </option>
        <?php } ?>
    </select>

    <input type="number" name="fee" placeholder="Rates" required>

    <input type="date" name="valid_from" required>

    <input type="date" name="valid_to" required>

    <select name="action" required>
        <option value="add">Add Fee</option>
        <option value="update">Update Fee</option>
    </select>

    <button type="submit" name="save_fee">Save Fee</button>

</form>
<hr>

    <h2>Payment Form</h2>

    <form method="POST">

        <input type="text" name="patient_name" placeholder="Patient Name" required>

        <select name="dentist_id" id="dentistSelect" required>
            <option value="">Select Dentist</option>

            <?php foreach ($dentists as $d) { ?>
                <option value="<?= $d['Dentist_ID']; ?>">
                    <?= $d['Dentist_FN'] . ' ' . $d['Dentist_LN']; ?>
                </option>
            <?php } ?>

        </select>

        <input type="text" name="amount" id="feeBox" readonly placeholder="Consultation Fee">

        <button type="submit" name="pay">Pay</button>

    </form>

</div>

<script>
document.getElementById("dentistSelect").addEventListener("change", function () {

    let id = this.value;
    let feeBox = document.getElementById("feeBox");

    if (!id) {
        feeBox.value = "";
        return;
    }

    fetch("payment.php?get_fee=1&dentist_id=" + encodeURIComponent(id))
        .then(res => res.text())
        .then(data => {

            data = data.trim();
            feeBox.value = (data === "" || data === "0") ? "0" : data;

        })
        .catch(() => {
            feeBox.value = "0";
        });

});
</script>

</body>
</html>