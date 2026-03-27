<?php
include("../config/database.php");

$language = $_GET['lang'] ?? 'english';

$sql = "SELECT * FROM paragraphs WHERE language=? ORDER BY RAND() LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$language);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo $data['content'] ?? "No paragraph found.";
?>

