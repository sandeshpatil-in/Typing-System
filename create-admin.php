<?php
include("config/database.php");

$username = "admin";
$password = password_hash("1234", PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

echo "Admin Created Successfully!";
?>