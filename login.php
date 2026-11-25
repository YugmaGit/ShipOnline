<?php
// Login page: checks customer number/password, redirects to request.php

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cust_no = $_POST['cust_no'];
    $password = $_POST['password'];

     $conn = @mysqli_connect( "localhost","root","","shiponline")
            or die("<p>Unable to connect to the database server.</p>"
                . "<p>Error code " . mysqli_connect_errno()
                . ": " . mysqli_connect_error() . "</p>");


    $stmt = $conn->prepare("SELECT password FROM customer WHERE cust_no = ?");
    $stmt->bind_param("i", $cust_no);
    $stmt->execute();
    $stmt->bind_result($db_password);
    $found = $stmt->fetch();
    $stmt->close();
    $conn->close();

    if (!$found || $db_password !== $password) {
        $error = "Incorrect customer number or password.";
    } else {
        // success -> redirect, passing cust_no as a parameter
        header("Location: request.php?cust_no=" . $cust_no);
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>ShipOnline Login</title>
	</head>

	<body style="background-color:#E5EEE4;">
	
	<?php include 'header.php'; ?>
	<h2>ShipOnline System Login Page</h2>

	<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
		
	<form method="post" action="login.php">
	
		Customer Number: <input type="text" name="cust_no"><br><br>
		Password: <input type="password" name="password"><br><br>
		
		<input type="submit" value="Log in">
	</form>

	<p><a href="shiponline.php">Home</a></p>
	
	</body>
</html>