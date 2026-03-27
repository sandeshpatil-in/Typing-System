<?php
session_start();
include("../config/database.php");

// CHECK LOGIN
if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['student_id'];

// FETCH STUDENT DATA
$sql = "SELECT * FROM students WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();


// FETCH RESULTS
$sql2 = "SELECT * FROM results WHERE student_id=? ORDER BY id DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i",$user_id);
$stmt2->execute();
$results = $stmt2->get_result();
?>

<?php include("../includes/header.php"); ?>

<div class="container mt-5 min-vh-100">

<h3>Welcome, <?php echo $user['name']; ?> 👋</h3>

<p>Plan Expiry: <b><?php echo $user['expiry_date']; ?></b></p>



<a href="../typing-preference.php" class="btn btn-dark mb-3">
Start Typing
</a>

<a href="#" class="btn btn-outline-dark mb-3">
Pay Subscription
</a>

<h5 class="text-center pb-3">My Results</h5>

<table class="table table-bordered border-1 border-dark">
<tr class="table-light border-1 border-dark">
<th>Date</th>
<th>Language</th>
<th>WPM</th>
<th>Accuracy</th>
</tr>

<?php while($row = $results->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['created_at']; ?></td>
<td class="text-capitalize"><?php echo $row['language']; ?></td>
<td><?php echo $row['wpm']; ?></td>
<td><?php echo $row['accuracy']; ?>%</td>
</tr>
<?php } ?>

</table>

</div>

<?php include("../includes/footer.php"); ?>