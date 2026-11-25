<?php

// Admin page: view requests by request date or pick-up date

$results = [];
$total_count = 0;
$total_revenue = 0;
$total_weight = 0;
$mode = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $day = $_POST['day']; $month = $_POST['month']; $year = $_POST['year'];
    $date = "$year-$month-$day";
    $mode = $_POST['date_type']; // "request" or "pickup"

     $conn = @mysqli_connect( "localhost","root","","shiponline")
            or die("<p>Unable to connect to the database server.</p>"
                . "<p>Error code " . mysqli_connect_errno()
                . ": " . mysqli_connect_error() . "</p>");

    if ($mode == "request") {
        $stmt = $conn->prepare(
            "SELECT cust_no, request_no, item_desc, weight, pickup_suburb,
                    pickup_datetime, delivery_suburb, delivery_state
             FROM request WHERE DATE(request_date) = ?"
        );
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['cost'] = 10 + max(0, $row['weight'] - 1) * 3;
            $total_revenue += $row['cost'];
            $results[] = $row;
            $total_count++;
        }

    } else {
        $stmt = $conn->prepare(
            "SELECT r.cust_no, c.name, c.phone, r.request_no, r.item_desc, r.weight,
                    r.pickup_address, r.pickup_suburb, r.pickup_datetime,
                    r.delivery_suburb, r.delivery_state
             FROM request r JOIN customer c ON r.cust_no = c.cust_no
             WHERE DATE(r.pickup_datetime) = ?
             ORDER BY r.pickup_suburb, r.delivery_state, r.delivery_suburb"
        );
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $total_weight += $row['weight'];
            $results[] = $row;
            $total_count++;
        }
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>ShipOnline Administration</title>
	</head>

	<body style="background-color:#E5EEE4;">
	
	<?php include 'header.php'; ?>
	
	<h2>ShipOnline System Administration Page</h2>

	<form method="post" action="admin.php">
  		
		Date for Retrieve:
			  <select name="day"><?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?></select>
			  <select name="month"><?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?></select>
			  <select name="year"><?php for($y=2026;$y<=2027;$y++) echo "<option>$y</option>"; ?></select><br><br>

		  Select Date Item:
			  <input type="radio" name="date_type" value="request" checked> Request Date
			  <input type="radio" name="date_type" value="pickup"> Pick-up Date<br><br>
			  <input type="submit" value="Show">
	</form>

	<?php if ($mode == "request"): ?>
  	
	<table border="1">
    		<tr>
		    <th>Cust No</th>
		    <th>Req No</th>
		    <th>Item</th>
		    <th>Weight</th>
        	    <th>Pickup Suburb</th>
		    <th>Pickup Date</th>
		    <th>Delivery Suburb</th>
		    <th>State</th>
		</tr>

		<?php foreach ($results as $r): ?>
    		<tr>
      			<td><?php echo $r['cust_no']; ?></td>
      			<td><?php echo $r['request_no']; ?></td>
      			<td><?php echo $r['item_desc']; ?></td>
      			<td><?php echo $r['weight']; ?></td>
      			<td><?php echo $r['pickup_suburb']; ?></td>
      			<td><?php echo $r['pickup_datetime']; ?></td>
      			<td><?php echo $r['delivery_suburb']; ?></td>
      			<td><?php echo $r['delivery_state']; ?></td>
    		</tr>
	        <?php endforeach; ?>
  	</table>
  	
	<p>Total requests: <?php echo $total_count; ?> | Total revenue: $<?php echo $total_revenue; ?></p>

	<?php elseif ($mode == "pickup"): ?>
	<table border="1">
		<tr><th>Cust No</th>
		<th>Name</th>
		<th>Phone</th>
		<th>Req No</th>
		<th>Item</th>
        	<th>Weight</th>
		<th>Pickup Addr</th>
		<th>Pickup Suburb</th>
		<th>Pickup Time</th>
        	<th>Delivery Suburb</th>
		<th>State</th></tr>
    	
	<?php foreach ($results as $r): ?>
    	<tr>
      		<td><?php echo $r['cust_no']; ?></td>
      		<td><?php echo $r['name']; ?></td>
      		<td><?php echo $r['phone']; ?></td>
      		<td><?php echo $r['request_no']; ?></td>
      		<td><?php echo $r['item_desc']; ?></td>
      		<td><?php echo $r['weight']; ?></td>
      		<td><?php echo $r['pickup_address']; ?></td>
      		<td><?php echo $r['pickup_suburb']; ?></td>
      		<td><?php echo $r['pickup_datetime']; ?></td>
      		<td><?php echo $r['delivery_suburb']; ?></td>
      		<td><?php echo $r['delivery_state']; ?></td>
    	</tr>
    	
	<?php endforeach; ?>
  	</table>
  
	<p>Total requests: <?php echo $total_count; ?> | Total weight: <?php echo $total_weight; ?> kg</p>
	<?php endif; ?>

	<p><a href="shiponline.php">Home</a></p>

	</body>
</html>