<?php
include("../config/database.php");

if(isset($_POST['register'])){

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];

$sql = "INSERT INTO students (name,email,password,status)
        VALUES (?,?,?,0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss",$name,$email,$password);
$stmt->execute();

echo "<script>alert('Registered! Wait for admin activation');</script>";
}
?>

<?php include("../includes/header.php"); ?>

<div class="container border rounded-3 text-center w-50 py-5 my-5">

<h3>Student Register</h3>

<form class="justify-content-center" method="POST">

<input type="text" name="name" class="form-control mb-2" placeholder="Name" required>

<input type="email" name="email" class="form-control mb-2" placeholder="Email" required>

<input type="password" name="password" class="form-control mb-2" placeholder="Password" required>


<div class="pt-1 mb-4">
<button name="register" class="btn btn-dark w-25">Register</button>
</div>

<p class="mt-3">
Already have an account? <a href="login.php">Login Here</a>
</p>

</form>

</div>

<?php include("../includes/footer.php"); ?>