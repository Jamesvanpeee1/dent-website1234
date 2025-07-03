<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "brightsmile_db");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = $_POST['full_name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$address = $_POST['address'];

// Insert into database
$sql = "INSERT INTO patients (full_name, email, phone, address) 
        VALUES ('$name', '$email', '$phone', '$address')";

if ($conn->query($sql) === TRUE) {
  echo "Registration successful!";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
