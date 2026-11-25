<?php

// Request page: takes shipping request, validates, stores, emails confirmation

$cust_no = isset($_GET['cust_no']) ? $_GET['cust_no'] : (isset($_POST['cust_no']) ? $_POST['cust_no'] : null);
$errors = [];
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $item_desc = trim($_POST['item_desc']);
    $weight = $_POST['weight'];
    $pickup_address = trim($_POST['pickup_address']);
    $pickup_suburb = trim($_POST['pickup_suburb']);

    // Build the date in Y-m-d order
    $pickup_day   = $_POST['pickup_day'];
    $pickup_month = $_POST['pickup_month'];
    $pickup_year  = $_POST['pickup_year'];
    $pickup_date_sql = sprintf("%04d-%02d-%02d", $pickup_year, $pickup_month, $pickup_day);

    // for display/email messages
    $pickup_date_display = "$pickup_day-$pickup_month-$pickup_year";

    $pickup_hour   = $_POST['pickup_hour'];
    $pickup_minute = $_POST['pickup_minute'];
    $pickup_time = sprintf("%02d:%02d", $pickup_hour, $pickup_minute);

    $receiver_name = trim($_POST['receiver_name']);
    $delivery_address = trim($_POST['delivery_address']);
    $delivery_suburb = trim($_POST['delivery_suburb']);
    $delivery_state = $_POST['state'];

    // (a) all inputs given
    if ($item_desc == "" || $pickup_address == "" || $pickup_suburb == ""
        || $receiver_name == "" || $delivery_address == "" || $delivery_suburb == "") {
        $errors[] = "All fields are required.";
    }

    // (b) weight range - also i used a 1-20 dropdown.
    // if ($weight < 1 || $weight > 20) $errors[] = "Weight must be between 1 and 20kg.";

    // (c) pickup at least 24 hours from now, using strtotime
    $pickup_datetime_str = "$pickup_date_sql $pickup_time:00";
    $pickup_ts = strtotime($pickup_datetime_str);
    $now_ts = time();
    if ($pickup_ts === false || ($pickup_ts - $now_ts) < 24 * 3600) {
        $errors[] = "Pick-up date/time must be at least 24 hours from now.";
    }

    // (d) pickup time between 9:00 and 17:00
    $hour = (int)$pickup_hour;
	if ($hour < 9 || $hour > 17 || ($hour == 17 && $pickup_minute > 0)) {
	    $errors[] = "Pick-up time must be between 9:00 and 17:00.";
	}

	if (empty($errors)) {

       		 $conn = @mysqli_connect( "localhost","root","","shiponline")
        	    or die("<p>Unable to connect to the database server.</p>"
        	        . "<p>Error code " . mysqli_connect_errno()
        	        . ": " . mysqli_connect_error() . "</p>");

        $request_date = date("Y-m-d H:i:s"); // generated request date = now

        $stmt = $conn->prepare(
            "INSERT INTO request (cust_no, request_date, item_desc, weight,
             pickup_address, pickup_suburb, pickup_datetime, receiver_name,
             delivery_address, delivery_suburb, delivery_state)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        // 11 columns -> 11 letters, in the same order as the variables below:
           $stmt->bind_param(
            "ississsssss",
            $cust_no, $request_date, $item_desc, $weight,
            $pickup_address, $pickup_suburb, $pickup_datetime_str, $receiver_name,
            $delivery_address, $delivery_suburb, $delivery_state
        );

        $stmt->execute();

        if ($stmt->error) {
            $errors[] = "Could not save your request: " . $stmt->error;
        } else {
            $request_no = $conn->insert_id;


            // calculate cost: $10 for first kg, $3 each additional kg
            $cost = 10 + max(0, $weight - 1) * 3;

            $success_msg = "Thank you! Your request number is $request_no. The weight is $weight. "
                          . "The cost is \$$cost. We will pick-up the item at $pickup_time on $pickup_date_display.";


            //  look up customer name/email and send confirmation email
            $stmt2 = $conn->prepare("SELECT name, email FROM customer WHERE cust_no = ?");
            $stmt2->bind_param("i", $cust_no);
            $stmt2->execute();
            $stmt2->bind_result($cust_name, $cust_email);
            $stmt2->fetch();
            $stmt2->close();

            $to = $cust_email;
            $subject = "shipping request with ShipOnline";
            $message = "Dear $cust_name, Thank you for using ShipOnline! Your request number is $request_no. "
                     . "The cost is \$$cost. We will pick-up the item at $pickup_time on $pickup_date_display.";

            $headers = "From: info@shiponline.com";
            // -r sets the envelope sender that receives bounce messages
//            mail($to, $subject, $message, $headers, "-r 106227407@student.swin.edu.au");
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>ShipOnline Request</title>
		<style>
			fieldset {
				margin-bottom: 20px;
				padding: 15px;
				border: 1px solid #999;
			}
			legend {
				font-weight: bold;
			}
			fieldset p {
				margin-bottom: 8px;
			}
		</style>
	</head>

	<body style="background-color:#E5EEE4;">

		<?php include 'header.php'; ?>

		<h2>ShipOnline System Request Page</h2>

		<p><strong>Pricing:</strong> $10 for the first 1kg, $3 for each additional kg.</p>

		<?php foreach ($errors as $e) echo "<p style='color:red;'>$e</p>"; ?>
		<?php if ($success_msg) echo "<p style='color:green;'>$success_msg</p>"; ?>

		<form method="post" action="request.php">

			<input type="hidden" name="cust_no" value="<?php echo htmlspecialchars($cust_no); ?>">

			<fieldset><legend>Item Information</legend>
				<p>Description: <input type="text" name="item_desc"></p>
				<p>Weight:
					<select name="weight">
						<?php for ($i = 1; $i <= 20; $i++) echo "<option value=\"$i\">$i</option>"; ?>
					</select>
				</p>
			</fieldset>

			<fieldset><legend>Pick-up Information</legend>
				<p>Address: <input type="text" name="pickup_address"></p>
				<p>Suburb: <input type="text" name="pickup_suburb"></p>
				<p>Preferred date:
					<select name="pickup_day"><?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?></select>
					<select name="pickup_month"><?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?></select>
					<select name="pickup_year"><?php for($y=2026;$y<=2027;$y++) echo "<option>$y</option>"; ?></select>
				</p>
				<p>Preferred time:
					<select name="pickup_hour"><?php for($h=0;$h<24;$h++) echo "<option>$h</option>"; ?></select> :
					<input type="text" name="pickup_minute" size="2" value="00">
				</p>
			</fieldset>

			<fieldset><legend>Delivery Information</legend>
				<p>Receiver Name: <input type="text" name="receiver_name"></p>
				<p>Address: <input type="text" name="delivery_address"></p>
				<p>Suburb: <input type="text" name="delivery_suburb"></p>
				<p>State:
					<select name="state">
						<option>VIC</option><option>NSW</option><option>QLD</option>
						<option>WA</option><option>SA</option><option>TAS</option>
						<option>ACT</option><option>NT</option>
					</select>
				</p>
			</fieldset>

			<input type="submit" value="Request">
		</form>

		<p><a href="shiponline.php">Home</a></p>
	</body>
</html>