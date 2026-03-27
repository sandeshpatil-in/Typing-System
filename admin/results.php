<?php
include("../config/database.php");

$sql = "SELECT results.*, students.name 
FROM results
JOIN students ON results.student_id = students.id";

$result = $conn->query($sql);

?>

<div class="container mt-5">

<h3 class="text-center pb-3">Results</h3>

<table class="table table-bordered border-1 border-dark">

<tr class="table-light border-1 border-dark">
<th>ID</th>
<th>Student</th>
<th>Language</th>
<th>WPM</th>
<th>Accuracy</th>
<th>Date</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td class="text-capitalize"><?php echo $row['language']; ?></td>
<td><?php echo $row['wpm']; ?></td>
<td><?php echo $row['accuracy']; ?>%</td>
<td><?php echo $row['created_at']; ?></td>
</tr>

<?php } ?>

</table>

</div>