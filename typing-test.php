<?php
session_start();
require_once("config/database.php");

// =====================
// AUTH CHECK
// =====================
if (!isset($_SESSION['student_id'])) {
    header("Location: account/login.php");
    exit();
}

$id = $_SESSION['student_id'];

// =====================
// FETCH USER
// =====================
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// =====================
// VALIDATION
// =====================
if (!$user) {
    die("User not found");
}

if ($user['status'] == 0) {
    die("Account not activated");
}

if (strtotime($user['expiry_date']) < time()) {
    die("Your plan expired");
}

// =====================
// GET TEST PARAMS
// =====================
$language   = $_GET['language'] ?? 'english';
$time       = (int)($_GET['time'] ?? 60);
$exam       = $_GET['exam_type'] ?? 'normal';
$passage_id = (int)($_GET['paragraph'] ?? 1);

// =====================
// FETCH PARAGRAPH
// =====================
$stmt = $conn->prepare("SELECT content FROM paragraphs WHERE language=? AND id=?");
$stmt->bind_param("si", $language, $passage_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$paragraph = $data['content'] ?? "No paragraph found.";
?>

<!DOCTYPE html>
<html>
<head>
<title>Typing Test</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;700;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<style>
body { 
  background:#fff;
  margin:0;
  font-family: 'Lato', sans-serif;
}

.topbar {
  color:#e0e0e0;
  padding:10px 25px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  flex-wrap:wrap;
}

.box {
  padding:15px;
  scroll-behavior: smooth;
}

#paragraphText, #typingArea {
  font-size:18px;
  line-height:1.6;
  font-family: 'Lato', sans-serif;
}

#paragraphText {
  border: 1px solid #333;
  padding: 15px;
  border-radius: 5px;
}

.full-height {
  height:calc(100vh - 70px);
}

textarea {
  resize:none;
  font-family: 'Lato', sans-serif;
}
</style>
</head>

<body>

<!-- ================= TOPBAR ================= -->
<div class="topbar bg-dark">
  <button class="btn btn-light btn-sm" onclick="exitTest()"><i class="fas fa-sign-out-alt"></i> Exit</button>

  <div>
    <i class="fas fa-clock"></i> Timer: <span id="timer">0:00</span>
  </div>

  <div class="d-flex gap-1 flex-wrap">
    <button class="btn btn-light btn-sm" onclick="setLayout('lr')" title="Left-Right"><i class="fas fa-arrows-alt-h">LR</i></button>
    <button class="btn btn-light btn-sm" onclick="setLayout('tb')" title="Top-Bottom"><i class="fas fa-arrows-alt-v">TB</i></button>
    <button class="btn btn-light btn-sm" onclick="setLayout('single')" title="Single View"><i class="fas fa-square">S</i></button>
    <button class="btn btn-light btn-sm" onclick="submitTest()"><i class="fas fa-check"></i> Submit</button>
  </div>
</div>

<!-- ================= MAIN ================= -->
<div class="container-fluid mt-3 full-height">
  <div id="typingContainer" class="row h-100">

    <!-- Paragraph -->
    <div id="paragraphBox" class="col-md-6 h-100">
      <div class="box overflow-auto h-100">
        <h5>Paragraph</h5>
        <div id="paragraphText"><?= htmlspecialchars($paragraph) ?></div>
      </div>
    </div>

    <!-- Typing -->
    <div id="typingBox" class="col-md-6 h-100">
      <div class="box h-100 d-flex flex-column">
        <h5>Start Typing</h5>
        <textarea id="typingArea" class="form-control flex-grow-1"
        placeholder="Start typing here..." autofocus></textarea>
      </div>
    </div>

  </div>
</div>

<!-- ================= SCRIPT ================= -->
<script>

let totalTime = <?= $time ?>;
let remainingTime = totalTime;

const timerEl = document.getElementById("timer");

// ================= TIMER =================
let timer = setInterval(() => {

  let min = Math.floor(remainingTime / 60);
  let sec = remainingTime % 60;

  timerEl.innerText = min + ":" + (sec < 10 ? "0"+sec : sec);

  remainingTime--;

  if (remainingTime < 0) {
    clearInterval(timer);
    submitTest();
  }

}, 1000);


// ================= EXIT =================
function exitTest(){
  if(confirm("Exit test?")){
    window.location.href = "typing-preference.php";
  }
}


// ================= SUBMIT =================
function submitTest(){

  clearInterval(timer);

  let typed = document.getElementById("typingArea").value;
  let original = document.getElementById("paragraphText").innerText;

  let correct = 0;

  for(let i = 0; i < typed.length; i++){
    if(typed[i] === original[i]){
      correct++;
    }
  }

  let accuracy = original.length > 0 ? (correct / original.length) * 100 : 0;
  accuracy = Math.round(accuracy);

  // FIXED WPM CALCULATION
  let timeTakenMin = (totalTime - remainingTime) / 60;
  let wpm = timeTakenMin > 0 ? Math.round((typed.length / 5) / timeTakenMin) : 0;

  fetch("api/save-result.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `wpm=${wpm}&accuracy=${accuracy}&language=<?= $language ?>`
  })
  .then(() => {

    alert(`Test Submitted\nWPM: ${wpm}\nAccuracy: ${accuracy}%`);

    window.location.href = `result.php?wpm=${wpm}&accuracy=${accuracy}`;
  });

}


// ================= LAYOUT =================
function setLayout(type){

  let container = document.getElementById("typingContainer");
  let para = document.getElementById("paragraphBox");
  let typing = document.getElementById("typingBox");

  if(type === "lr"){
    container.className = "row h-100";
    para.style.display = "block";
    para.className = "col-md-6 h-100";
    typing.className = "col-md-6 h-100";
  }

  else if(type === "tb"){
    container.className = "row flex-column h-100";
    para.style.display = "block";
    para.className = "col-12 mb-2";
    typing.className = "col-12";
  }

  else if(type === "single"){
    para.style.display = "none";
    typing.className = "col-12 h-100";
  }

  document.getElementById("typingArea").focus();
}

</script>

<!-- ================= REMINGTON ENGINE ================= -->
<script src="assets/js/remington-marathi.js"></script>
<script>
  initRemingtonTyping("<?= $language ?>");
</script>

</body>
</html>