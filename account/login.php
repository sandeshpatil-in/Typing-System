<?php
session_start();
include("../config/database.php");

if(isset($_POST['login'])){

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM students WHERE email=? AND password=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss",$email,$password);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows > 0){

$user = $result->fetch_assoc();

$_SESSION['student_id'] = $user['id'];

header("Location: dashboard.php");
exit();

}else{
$error = "Invalid Login!";
}

}
?>

<?php include("../includes/header.php"); ?>

<div class="container border rounded-3 text-center w-50 py-5 my-5">

<h3>Student Login</h3>

<?php if(isset($error)) echo "<p class='text-danger'>$error</p>"; ?>

<form method="POST">

<input type="email" name="email" class="form-control mb-2" placeholder="Email" required>

<input type="password" name="password" class="form-control mb-2" placeholder="Password" required>

<div class="pt-1 mb-4">
<button name="login" class="btn btn-dark w-25">Login</button>
</div>

<p class="mt-3">
New Student? <a href="register.php">Register Here</a>
</p>

</form>


</div>






<?php include("../includes/footer.php"); ?>