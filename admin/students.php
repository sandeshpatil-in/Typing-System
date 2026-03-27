<?php
include("../config/database.php");

// ADMIN LOGIN CHECK
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

// FETCH STUDENTS
$sql = "SELECT * FROM students ORDER BY id DESC";
$result = $conn->query($sql);
?>


<div class="container mt-5">

<h3 class="text-center pb-3">Manage Students</h3>

<table class="table table-bordered border-1 border-dark">

<tr class="table-light border-1 border-dark">
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>   <!-- LOOP START -->

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>

<td>
<?php if($row['status'] == 1){ ?>
<span class="text-success">Active</span><br>
<small>Expire: <?php echo $row['expiry_date']; ?></small>
<?php } else { ?>
<span class="text-danger">Inactive</span>
<?php } ?>
</td>

<td>
<?php if($row['status'] == 0){ ?>
<a href="activate-student.php?id=<?php echo $row['id']; ?>" 
class="btn btn-dark btn-sm">
Activate
</a>
<?php } else { ?>
<span class="text-muted">Activated</span>
<?php } ?>
</td>

</tr>

<?php } ?>  <!-- LOOP END -->

</table>

</div>

