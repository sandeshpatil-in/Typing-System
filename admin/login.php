<?php
session_start();
include("../config/database.php");

if(isset($_POST['login'])){

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM admins WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows > 0){

$admin = $result->fetch_assoc();

// VERIFY HASHED PASSWORD
if(password_verify($password, $admin['password'])){

$_SESSION['admin_id'] = $admin['id'];

header("Location: dashboard.php");
exit();

}else{
$error = "Wrong Password!";
}

}else{
$error = "Admin Not Found!";
}

}
?>

<?php include("../includes/header.php"); ?>

<div class="container border rounded-3 text-center w-50 py-5 my-5">

<h3>Admin Login</h3>

<?php if(isset($error)) echo "<p class='text-danger'>$error</p>"; ?>

<form method="POST">

<input type="text" name="username" class="form-control mb-2" placeholder="Username" required>

<input type="password" name="password" class="form-control mb-2" placeholder="Password" required>


<div class="pt-1 mb-4">
<button type="submit" name="login" class="btn btn-dark w-25">Login</button>
</div>

</form>

</div>



<?php include("../includes/footer.php"); ?>