<?php
session_start();
include("../config/database.php");

if(!isset($_SESSION['student_id'])){
    echo "Login required";
    exit();
}

$student_id = $_SESSION['student_id'];
$language = $_POST['language'];
$wpm = $_POST['wpm'];
$accuracy = $_POST['accuracy'];

$sql = "INSERT INTO results (student_id, language, wpm, accuracy)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isid", $student_id, $language, $wpm, $accuracy);
$stmt->execute();

echo "success";
?>