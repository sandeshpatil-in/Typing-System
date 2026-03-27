<?php
include("../config/database.php");

$id = $_GET['id'];

// Activate for 30 days
$expiry = date('Y-m-d', strtotime('+30 days'));

$sql = "UPDATE students 
        SET status=1, expiry_date=? 
        WHERE id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $expiry, $id);
$stmt->execute();

header("Location: students.php");
?>