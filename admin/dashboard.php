

<?php
session_start();

if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<?php include("../includes/header.php"); ?>

<div class="container-fluid px-0 mx-0">
  <div class="row min-vh-100">

    <!-- SIDEBAR -->
    <div class="col-md-3 col-lg-2 bg-dark text-white p-3 justify-content-between d-flex flex-column">


      <ul class="nav flex-column">

        <li class="nav-item mb-4 border-bottom">
          <a href="dashboard.php?page=home" class="nav-link text-white">Dashboard</a>
        </li>


        <li class="nav-item mb-2">
<a href="dashboard.php?page=students" 
class="nav-link text-white <?php if($page=='students') echo 'fw-bold'; ?>">
Students
</a>        </li>

        
        <li class="nav-item mb-2">
          <a href="dashboard.php?page=paragraphs" class="nav-link text-white">Paragraphs</a>
        </li>

        <li class="nav-item mb-2">
          <a href="dashboard.php?page=results" class="nav-link text-white">Results</a>
        </li>

        <li class="nav-item mb-2">
          <a href="dashboard.php?page=subscriptions" class="nav-link text-white">Subscriptions</a>
        </li>

        <li class="nav-item mt-4">
          <a href="logout.php" class="nav-link text-danger">Logout</a>
        </li>

      </ul>
    </div>


    <!-- MAIN CONTENT -->
    <div class="col-md-9 col-lg-10 p-4">

      <?php
        switch($page){

          case 'students':
            include("students.php");
            break;

          case 'activate':
            include("activate-student.php");
            break;

          case 'paragraphs':
            include("paragraphs.php");
            break;

          case 'results':
            include("results.php");
            break;

          default:
            echo "<h3 class='fw-bold'>Welcome to Admin Dashboard</h3>";
            echo "<p>Use the sidebar to manage the system.</p>";
        }
      ?>
    </div>

  </div>
</div>


<?php include("../includes/footer.php"); ?>

