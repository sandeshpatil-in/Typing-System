<?php
include("../config/database.php");

$id = $_GET['id'];

$result = $conn->query("SELECT * FROM paragraphs WHERE id=$id");
$row = $result->fetch_assoc();

if(isset($_POST['update'])){

$content = $_POST['content'];

$conn->query("UPDATE paragraphs SET content='$content' WHERE id=$id");

header("Location: paragraphs.php");

}

include("../includes/header.php");
?>

<div class="container mt-5 min-vh-100">

<h3 class="text-center pb-3">Edit Paragraph</h3>

<form method="POST">

<textarea name="content" class="form-control mb-3 border-1 border-dark" rows="6">
<?php echo $row['content']; ?>
</textarea>

<button name="update" class="btn btn-dark">Update</button>

</form>

</div>

<?php include("../includes/footer.php"); ?>