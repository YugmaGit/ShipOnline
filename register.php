<?php

// Registration page: collects and validates new customer info

$errors = [];
$success_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $name = trim($_POST['name']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // (a) all inputs given
    if ($name == "" || $password == "" || $confirm == "" || $email == "" || $phone == "")
    {
        $errors[] = "All fields are required.";
    }

    // (b) password match
    if ($password !== $confirm)
    {
        $errors[] = "Passwords do not match.";
    }

    // Only touch the database if validation so far has passed
    if (empty($errors))
    {
        $conn = @mysqli_connect("localhost","root","","shiponline")
            or die("<p>Unable to connect to the database server.</p>"
                . "<p>Error code " . mysqli_connect_errno()
                . ": " . mysqli_connect_error() . "</p>");


        // (c) email uniqueness check
        $stmt = $conn->prepare("SELECT cust_no FROM customer WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0)
        {
            $errors[] = "This email is already registered.";
            $stmt->close();
        }
        else
        {
            $stmt->close();
            $insert = $conn->prepare(
                "INSERT INTO customer (name, password, email, phone) VALUES (?, ?, ?, ?)"
            );
            $insert->bind_param("ssss", $name, $password, $email, $phone);
            $insert->execute();

            if ($insert->error)
            {
                $errors[] = "Registration failed: " . $insert->error;
            }
            else
            {
                $cust_no = $conn->insert_id; // the generated customer number
                $success_msg = "Dear $name, you are successfully registered into ShipOnline, "
                              . "and your customer number is $cust_no, which will be used to get into the system.";
            }
            $insert->close();
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>ShipOnline Registration</title>
	</head>
	<body style="background-color:#E5EEE4;">
		<?php include 'header.php'; ?>
		<h2>ShipOnline System Registration Page</h2>
		<?php foreach ($errors as $e) echo "<p style='color:red;'>$e</p>"; ?>
		<?php if ($success_msg) echo "<p style='color:green;'>$success_msg</p>"; ?>
		<form method="post" action="register.php">
			  Name: <input type="text" name="name"><br><br>
			  Password: <input type="password" name="password"><br><br>
  			  Confirm Password: <input type="password" name="confirm_password"><br><br>
  			  Email Address: <input type="text" name="email"><br><br>
  			  Contact Phone: <input type="text" name="phone"><br><br>
			<input type="submit" value="Register">
		</form>
		<p><a href="shiponline.php">Home</a></p>
	</body>
</html>