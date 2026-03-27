<?php
include("../config/database.php");

$result = $conn->query("SELECT * FROM paragraphs");

?>

<div class="container mt-5">

<h3 class="text-center pb-3" >Paragraphs</h3>

<a href="add-paragraph.php" class="btn btn-dark mb-3">Add Paragraph</a>

<table class="table table-bordered border-1 border-dark">

<tr class="table-light border-1 border-dark">
<th>ID</th>
<th>Language</th>
<th>Content</th>
<th>Action</th>
<th>Delete</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td class="text-capitalize"><?php echo $row['language']; ?></td>
<td><?php echo substr($row['content'],0,100); ?></td>

<td>
<a href="edit-paragraph.php?id=<?php echo $row['id']; ?>" class="btn btn-dark">
Edit
</a>
</td>

<td>
<a href="delete-paragraph.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-dark">
Delete
</a>
</td>

</tr>

<?php } ?>

</table>

</div>

