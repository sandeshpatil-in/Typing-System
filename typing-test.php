<?php
require_once __DIR__ . '/includes/init.php';

if (!isStudentLoggedIn()) {
    syncGuestAttemptsWithClient($_GET['guest_attempts_used'] ?? null);
}

$access = requireTypingAccess($conn);

$languageId = (int) getSafeGet('language_id', 0);
$examTypeId = (int) getSafeGet('exam_type_id', 0);
$passageId = (int) getSafeGet('paragraph', 1);
$language = getSafeGet('language', 'english');
$time = (int) getSafeGet('time', 60);
$exam = getSafeGet('exam_type', 'normal');

$paragraph = "No paragraph found.";

if (dbTableExists($conn, 'passages') && dbTableExists($conn, 'languages') && dbTableExists($conn, 'exam_types') && $languageId > 0 && $examTypeId > 0) {
    $stmt = $conn->prepare(
        "SELECT p.content, l.name AS language_name, e.name AS exam_type_name, e.time_limit
         FROM passages p
         INNER JOIN languages l ON l.id = p.language_id
         INNER JOIN exam_types e ON e.id = p.exam_type_id
         WHERE p.id = ? AND p.language_id = ? AND p.exam_type_id = ?"
    );
    $stmt->bind_param("iii", $passageId, $languageId, $examTypeId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        $paragraph = $data['content'];
        $language = strtolower($data['language_name']);
        $exam = $data['exam_type_name'];
        if (empty($_GET['time'])) {
            $time = (int) $data['time_limit'];
        }
    }
} else {
    $stmt = $conn->prepare("SELECT content FROM paragraphs WHERE language = ? AND id = ?");
    $stmt->bind_param("si", $language, $passageId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $paragraph = $data['content'] ?? "No paragraph found.";
}
?>

<!DOCTYPE html>
<html lang="en">
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
  gap: 12px;
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
<div class="topbar bg-dark">
  <button type="button" class="btn btn-light btn-sm" onclick="exitTest()"><i class="fas fa-sign-out-alt"></i> Exit</button>

  <div>
    <i class="fas fa-clock"></i> Timer: <span id="timer">0:00</span>
  </div>

  <div>
    <?php if ($access['is_logged_in']) { ?>
      <span class="badge text-bg-light">Unlimited access</span>
    <?php } else { ?>
      <span class="badge text-bg-warning text-dark">Guest tests left: <span id="guestRemainingBadge"><?php echo (int) $access['guest_tests_remaining']; ?></span></span>
    <?php } ?>
  </div>

  <div class="d-flex gap-1 flex-wrap">
    <button type="button" class="btn btn-light btn-sm" onclick="setLayout('lr')" title="Left-Right"><i class="fas fa-arrows-alt-h">LR</i></button>
    <button type="button" class="btn btn-light btn-sm" onclick="setLayout('tb')" title="Top-Bottom"><i class="fas fa-arrows-alt-v">TB</i></button>
    <button type="button" class="btn btn-light btn-sm" onclick="setLayout('single')" title="Single View"><i class="fas fa-square">S</i></button>
    <button type="button" id="submitTestBtn" class="btn btn-light btn-sm" onclick="submitTest()"><i class="fas fa-check"></i> Submit</button>
  </div>
</div>

<div class="container-fluid mt-3 full-height">
  <div id="typingContainer" class="row h-100">
    <div id="paragraphBox" class="col-md-6 h-100">
      <div class="box overflow-auto h-100">
        <h5>Paragraph</h5>
        <div id="paragraphText"><?php echo htmlspecialchars($paragraph); ?></div>
      </div>
    </div>

    <div id="typingBox" class="col-md-6 h-100">
      <div class="box h-100 d-flex flex-column">
        <h5>Start Typing</h5>
        <textarea id="typingArea" class="form-control flex-grow-1" placeholder="Start typing here..." autofocus></textarea>
      </div>
    </div>
  </div>
</div>

<script>
const totalTime = <?php echo (int) $time; ?>;
let remainingTime = totalTime;
const timerEl = document.getElementById('timer');
const submitTestBtn = document.getElementById('submitTestBtn');
const isLoggedIn = <?php echo $access['is_logged_in'] ? 'true' : 'false'; ?>;
const csrfToken = '<?php echo htmlspecialchars(csrfToken()); ?>';
let isSubmitting = false;
const testPayload = {
  language: '<?php echo htmlspecialchars($language, ENT_QUOTES); ?>',
  exam_type: '<?php echo htmlspecialchars($exam, ENT_QUOTES); ?>',
  paragraph_id: '<?php echo (int) $passageId; ?>',
  time_limit_seconds: '<?php echo (int) $time; ?>'
};

let timer = setInterval(() => {
  const min = Math.floor(remainingTime / 60);
  const sec = remainingTime % 60;
  timerEl.innerText = min + ':' + (sec < 10 ? '0' + sec : sec);

  if (remainingTime <= 0) {
    clearInterval(timer);
    submitTest();
    return;
  }

  remainingTime--;
}, 1000);

function exitTest() {
  if (confirm('Exit test?')) {
    window.location.href = 'typing-preference.php';
  }
}

async function submitTest() {
  if (isSubmitting) {
    return;
  }

  isSubmitting = true;
  clearInterval(timer);
  if (submitTestBtn) {
    submitTestBtn.disabled = true;
  }

  const typed = document.getElementById('typingArea').value;
  const original = document.getElementById('paragraphText').innerText;
  let correct = 0;

  for (let i = 0; i < typed.length; i++) {
    if (typed[i] === original[i]) {
      correct++;
    }
  }

  let accuracy = original.length > 0 ? (correct / original.length) * 100 : 0;
  accuracy = Math.round(accuracy);

  const effectiveRemainingTime = Math.max(0, remainingTime);
  const timeTakenMin = Math.max(1 / 60, (totalTime - effectiveRemainingTime) / 60);
  const wpm = timeTakenMin > 0 ? Math.round((typed.length / 5) / timeTakenMin) : 0;
  const typedWords = typed.trim() ? typed.trim().split(/\s+/).length : 0;

  const body = new URLSearchParams({
    csrf_token: csrfToken,
    wpm: String(wpm),
    accuracy: String(accuracy),
    typed_words: String(typedWords),
    language: testPayload.language,
    exam_type: testPayload.exam_type,
    paragraph_id: testPayload.paragraph_id,
    time_limit_seconds: testPayload.time_limit_seconds,
    guest_attempts_used: String(Number(localStorage.getItem('guestTestAttempts') || '0'))
  });

  try {
    const response = await fetch('api/save-result.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body
    });

    const rawResponse = await response.text();
    let data = null;

    try {
      data = JSON.parse(rawResponse);
    } catch (parseError) {
      data = {
        success: false,
        message: 'Unexpected server response while saving the result.'
      };
    }

    if (!response.ok || !data.success) {
      alert(data.message || 'Unable to save test result.');

      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }

      window.location.href = `result.php?wpm=${wpm}&accuracy=${accuracy}&words=${typedWords}&remaining=${<?php echo (int) $access['guest_tests_remaining']; ?>}&access=${isLoggedIn ? 'paid' : 'guest'}`;
      return;
    }

    if (!isLoggedIn) {
      localStorage.setItem('guestTestAttempts', String(<?php echo GUEST_TEST_LIMIT; ?> - data.guest_tests_remaining));
      localStorage.setItem('guestTestsRemaining', String(data.guest_tests_remaining));

      const badge = document.getElementById('guestRemainingBadge');
      if (badge) {
        badge.textContent = data.guest_tests_remaining;
      }
    }

    window.location.href = `result.php?wpm=${wpm}&accuracy=${accuracy}&words=${typedWords}&remaining=${data.guest_tests_remaining}&access=${data.access_type}`;
  } catch (error) {
    alert('Unable to contact the server. Showing your result page anyway.');
    window.location.href = `result.php?wpm=${wpm}&accuracy=${accuracy}&words=${typedWords}&remaining=${<?php echo (int) $access['guest_tests_remaining']; ?>}&access=${isLoggedIn ? 'paid' : 'guest'}`;
  }
}

function setLayout(type) {
  const container = document.getElementById('typingContainer');
  const para = document.getElementById('paragraphBox');
  const typing = document.getElementById('typingBox');

  if (type === 'lr') {
    container.className = 'row h-100';
    para.style.display = 'block';
    para.className = 'col-md-6 h-100';
    typing.className = 'col-md-6 h-100';
  } else if (type === 'tb') {
    container.className = 'row flex-column h-100';
    para.style.display = 'block';
    para.className = 'col-12 mb-2';
    typing.className = 'col-12';
  } else if (type === 'single') {
    para.style.display = 'none';
    typing.className = 'col-12 h-100';
  }

  document.getElementById('typingArea').focus();
}
</script>

<script src="assets/js/remington-marathi.js"></script>
<script>
initRemingtonTyping('<?php echo htmlspecialchars($language, ENT_QUOTES); ?>');
</script>

</body>
</html>
