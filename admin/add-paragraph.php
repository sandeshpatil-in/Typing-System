<?php
include("../config/database.php");

if(isset($_POST['add'])){

$language = $_POST['language'];
$content = $_POST['content'];

$sql = "INSERT INTO paragraphs(language,content) VALUES (?,?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss",$language,$content);
$stmt->execute();

echo "Paragraph added";
}

include("../includes/header.php");
?>

<div class="container my-5 min-vh-100">

<h3 class="text-center pb-3">Add Paragraph</h3>

<form method="POST">

<select name="language" class="form-control mb-3 border-1 border-dark">
<option value="english">English</option>
<option value="marathi">Marathi</option>
</select>

<textarea name="content" required class="form-control mb-3 border-1 border-dark" rows="6"></textarea>

<button name="add" class="btn btn-dark teaxt-center">Add Paragraph</button>

</form>

</div>

<?php include("../includes/footer.php"); ?>