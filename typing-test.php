<?php
require_once __DIR__ . '/includes/init.php';
/** @var mysqli $conn */

if (!isStudentLoggedIn()) {
    syncGuestAttemptsWithClient($_GET['guest_attempts_used'] ?? null, $conn);
}

$access = requireTypingAccess($conn);

$languageId = (int) getSafeGet('language_id', 0);
$examTypeId = (int) getSafeGet('exam_type_id', 0);
$passageId = (int) getSafeGet('paragraph', 1);
$language = getSafeGet('language', 'english');
$time = (int) getSafeGet('time', 60);
$exam = getSafeGet('exam_type', 'normal');
$allowBackspace = strtolower((string) getSafeGet('backspace', 'on')) !== 'off';
$baseFontSize = 16;
$normalizedLanguage = strtolower(trim((string) $language));
$isIndicLanguage = in_array($normalizedLanguage, ['marathi', 'hindi'], true);

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
        $normalizedLanguage = strtolower(trim((string) $language));
        $isIndicLanguage = in_array($normalizedLanguage, ['marathi', 'hindi'], true);
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
<title><?php echo APP_NAME; ?> - Typing Test</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;700;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">


<style>
:root {
  --typing-font-latin: 'Lato', sans-serif;
  --typing-font-indic-default: 'Mangal', 'Nirmala UI', 'Noto Sans Devanagari', sans-serif;
}
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
  min-height: 240px;
}
#paragraphText, #typingArea {
  font-size:12px;
  line-height:1.6;
}
.indic-text {
  font-family: 'Mangal', 'Nirmala UI', 'Noto Sans Devanagari', sans-serif !important;
}
.latin-text {
  font-family: 'Times New Roman', Times, serif !important;
}
#paragraphText {
  border: 1px solid #333;
  padding: 15px;
  border-radius: 5px;
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  user-select: none;
}
.full-height {
  height:calc(100vh - 70px);
}
textarea {
  resize:none;
  font-family: 'Lato', sans-serif;
}
.layout-tb #paragraphBox .box,
.layout-tb #typingBox .box {
  max-height: 45vh;
}
.topbar .toolbar-group {
  display:flex;
  align-items:center;
  flex-wrap:wrap;
  gap:8px;
}
.floating-keyboard {
  position: fixed;
  top: 88px;
  right: 24px;
  width: min(560px, calc(100vw - 32px));
  min-width: 280px;
  max-width: 90vw;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid #222;
  border-radius: 4px;
  box-shadow: 0 1px 5px rgba(0, 0, 0, 0.24);
  overflow: hidden;
  z-index: 1055;
  resize: both;
}
.floating-keyboard.d-none {
  display: none !important;
}
.keyboard-header {
  cursor: move;
  background: #111;
  padding: 4px 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.keyboard-header strong {
  font-size: 14px;
}
.keyboard-tools {
  display: flex;
  align-items: center;
  gap: 8px;

}
.keyboard-tools button {
  border: 0;
  border-radius: 2px;
  background: #f3f3f3;
  color: #111;
  padding: 6px 6px;
  font-size: 12px;
}
.keyboard-body {
  background: #f8f8f8;
  padding: 0px;
}
.keyboard-body img {
  display: block;
  width: 100%;
  height: auto;
  border: 1px solid #d4d4d4;
}

@media (max-width: 768px) {
  .topbar {
    padding: 12px;
  }
  .floating-keyboard {
    right: 12px;
    left: 12px;
    width: auto;
    min-width: 0;
    max-width: none;
  }
}
</style>


</head>

<body>
<div class="topbar bg-dark">
  <button type="button" class="btn btn-light btn-sm" onclick="exitTest()"><i class="fas fa-sign-out-alt"></i> Exit</button>

  <div>
    <i class="fas fa-clock"></i> Timer: <span id="timer">0:00</span>
  </div>

  <!-- <div>
     <?php if ($access['is_logged_in']) { ?>
      <span class="badge text-bg-light">Unlimited access</span>
    <?php } else { ?>
    <span class="badge text-bg-warning text-dark">Guest tests left: <span id="guestRemainingBadge"><?php echo (int) $access['guest_tests_remaining']; ?></span></span>
    <?php } ?>
  </div> -->

  

  <div class="toolbar-group">
    <?php if ($isIndicLanguage) { ?>
      <button type="button" id="keyboardToggleBtn" class="btn btn-light btn-sm"><i class="fas fa-keyboard"></i>Keyboard</button>
    <?php } ?>

    <button type="button" class="btn btn-light btn-sm" onclick="setLayout('lr')" title="Left-Right"><i class="fas fa-arrows-alt-h">LR</i></button>
    <button type="button" class="btn btn-light btn-sm" onclick="setLayout('tb')" title="Top-Bottom"><i class="fas fa-arrows-alt-v">TB</i></button>
    <button type="button" class="btn btn-light btn-sm" onclick="setLayout('single')" title="Single View"><i class="fas fa-square">S</i></button>
    <div class="d-flex align-items-center gap-1 ms-2">
      <button type="button" id="fontSizeDown" class="btn btn-light btn-sm" title="Decrease font size">A-</button>
      <span id="fontSizeValue" class="text-light small px-1">12px</span>
      <button type="button" id="fontSizeUp" class="btn btn-light btn-sm" title="Increase font size">A+</button>
    </div>
    <button type="button" id="submitTestBtn" class="btn btn-light btn-sm" onclick="submitTest()"><i class="fas fa-check"></i> Submit</button>
  </div>
</div>

<div class="container-fluid mt-3 full-height">
  <div id="typingContainer" class="row h-100">
    <div id="paragraphBox" class="col-md-6 h-100">
      <div class="box overflow-auto h-100">
        <h5>Paragraph</h5>
        <pre id="paragraphText" class="<?php echo $isIndicLanguage ? 'indic-text' : 'latin-text'; ?>" style="font-size: <?php echo $baseFontSize; ?>px;"><?php echo htmlspecialchars($paragraph); ?></pre>
      </div>
    </div>

    <div id="typingBox" class="col-md-6 h-100">
      <div class="box h-100 d-flex flex-column">
        <h5>Start Typing</h5>
        <textarea id="typingArea" class="form-control flex-grow-1 <?php echo $isIndicLanguage ? 'indic-text' : 'latin-text'; ?>" placeholder="Start typing here..." autofocus style="font-size: <?php echo $baseFontSize; ?>px;"></textarea>
      </div>
    </div>
  </div>
</div>

<?php if ($isIndicLanguage) { ?>
  <div id="keyboardPreview" class="floating-keyboard d-none">
    <div id="keyboardDragHandle" class="keyboard-header">
      <div class="keyboard-tools">
        <button type="button" id="keyboardSizeDown"><i class="fas fa-minus"></i></button>
        <button type="button" id="keyboardSizeUp"><i class="fas fa-plus"></i></button>
        <button type="button" id="keyboardCloseBtn"><i class="fas fa-times"></i></button>
      </div>
    </div>
    <div class="keyboard-body">
      <img src="assets/images/ISM FONT.png" alt="Marathi and Hindi exam keyboard reference">
      <!-- <div class="keyboard-note">Drag to move. Resize from the bottom-right corner. The same preview is available while Marathi or Hindi typing is open.</div> -->
    </div>
  </div>
<?php } ?>

<script>
const totalTime = <?php echo (int) $time; ?>;
let remainingTime = totalTime;
const timerEl = document.getElementById('timer');
const submitTestBtn = document.getElementById('submitTestBtn');
const isLoggedIn = <?php echo $access['is_logged_in'] ? 'true' : 'false'; ?>;
const isIndicLanguage = <?php echo $isIndicLanguage ? 'true' : 'false'; ?>;
const allowBackspace = <?php echo $allowBackspace ? 'true' : 'false'; ?>;
const typingOptions = {
  allowBackspace: allowBackspace
};
const csrfToken = '<?php echo htmlspecialchars(csrfToken()); ?>';
let isSubmitting = false;
const testPayload = {
  language: '<?php echo htmlspecialchars($language, ENT_QUOTES); ?>',
  exam_type: '<?php echo htmlspecialchars($exam, ENT_QUOTES); ?>',
  paragraph_id: '<?php echo (int) $passageId; ?>',
  time_limit_seconds: '<?php echo (int) $time; ?>'
};
const originalParagraph = <?php echo json_encode($paragraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const paragraphText = document.getElementById('paragraphText');
const typingArea = document.getElementById('typingArea');
const keyboardPreview = document.getElementById('keyboardPreview');
const keyboardToggleBtn = document.getElementById('keyboardToggleBtn');
const keyboardCloseBtn = document.getElementById('keyboardCloseBtn');
const keyboardDragHandle = document.getElementById('keyboardDragHandle');
const keyboardSizeUp = document.getElementById('keyboardSizeUp');
const keyboardSizeDown = document.getElementById('keyboardSizeDown');
const keyboardStorageKey = `floatingKeyboard:${testPayload.language}`;
const fontSizeValue = document.getElementById('fontSizeValue');
const fontSizeDownBtn = document.getElementById('fontSizeDown');
const fontSizeUpBtn = document.getElementById('fontSizeUp');
let currentFontSize = <?php echo $baseFontSize; ?>;
if (fontSizeValue) {
  fontSizeValue.textContent = `${currentFontSize}px`;
}

function applyFontSize(size) {
  const clamped = Math.max(10, Math.min(32, size));
  if (paragraphText) paragraphText.style.fontSize = `${clamped}px`;
  if (typingArea) typingArea.style.fontSize = `${clamped}px`;
  currentFontSize = clamped;
  if (fontSizeValue) {
    fontSizeValue.textContent = `${clamped}px`;
  }
}

applyFontSize(currentFontSize);

if (typingArea && !typingOptions.allowBackspace) {
  typingArea.addEventListener('keydown', (event) => {
    if (event.key === 'Backspace' && !event.ctrlKey && !event.metaKey) {
      event.preventDefault();
    }
  });
}

if (fontSizeDownBtn) {
  fontSizeDownBtn.addEventListener('click', () => applyFontSize(currentFontSize - 1));
}

if (fontSizeUpBtn) {
  fontSizeUpBtn.addEventListener('click', () => applyFontSize(currentFontSize + 1));
}

if (paragraphText) {
  paragraphText.addEventListener('copy', (event) => event.preventDefault());
  paragraphText.addEventListener('cut', (event) => event.preventDefault());
  paragraphText.addEventListener('contextmenu', (event) => event.preventDefault());
  paragraphText.addEventListener('selectstart', (event) => event.preventDefault());
}

function showKeyboardPreview(show) {
  if (!keyboardPreview) {
    return;
  }

  const currentState = JSON.parse(localStorage.getItem(keyboardStorageKey) || '{}');
  const rect = keyboardPreview.getBoundingClientRect();

  if (rect.width > 0) {
    currentState.width = keyboardPreview.style.width || `${Math.round(rect.width)}px`;
    currentState.top = keyboardPreview.style.top || `${Math.round(rect.top)}px`;
    currentState.left = keyboardPreview.style.left || '';
    currentState.right = keyboardPreview.style.right || `${Math.max(12, Math.round(window.innerWidth - rect.right))}px`;
  }

  currentState.visible = show;
  localStorage.setItem(keyboardStorageKey, JSON.stringify(currentState));

  keyboardPreview.classList.toggle('d-none', !show);
}

function restoreKeyboardPreview() {
  if (!keyboardPreview) {
    return;
  }

  const state = JSON.parse(localStorage.getItem(keyboardStorageKey) || '{}');

  if (state.width) {
    keyboardPreview.style.width = state.width;
  }

  if (state.top) {
    keyboardPreview.style.top = state.top;
  }

  if (state.left) {
    keyboardPreview.style.left = state.left;
    keyboardPreview.style.right = 'auto';
  } else if (state.right) {
    keyboardPreview.style.right = state.right;
  }

  if (state.visible) {
    keyboardPreview.classList.remove('d-none');
  }
}

function attachKeyboardDrag() {
  if (!keyboardPreview || !keyboardDragHandle) {
    return;
  }

  let isDragging = false;
  let dragOffsetX = 0;
  let dragOffsetY = 0;

  const onPointerMove = (event) => {
    if (!isDragging) {
      return;
    }

    const nextLeft = Math.min(Math.max(8, event.clientX - dragOffsetX), window.innerWidth - 80);
    const nextTop = Math.min(Math.max(8, event.clientY - dragOffsetY), window.innerHeight - 80);

    keyboardPreview.style.left = `${nextLeft}px`;
    keyboardPreview.style.top = `${nextTop}px`;
    keyboardPreview.style.right = 'auto';
  };

  const onPointerUp = () => {
    if (!isDragging) {
      return;
    }

    isDragging = false;
    document.body.style.userSelect = '';
    showKeyboardPreview(!keyboardPreview.classList.contains('d-none'));
  };

  keyboardDragHandle.addEventListener('pointerdown', (event) => {
    if (event.target.closest('button')) {
      return;
    }

    isDragging = true;
    const rect = keyboardPreview.getBoundingClientRect();
    dragOffsetX = event.clientX - rect.left;
    dragOffsetY = event.clientY - rect.top;
    keyboardPreview.style.left = `${rect.left}px`;
    keyboardPreview.style.top = `${rect.top}px`;
    keyboardPreview.style.right = 'auto';
    document.body.style.userSelect = 'none';
  });

  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
}

function persistKeyboardPreviewState() {
  if (!keyboardPreview || keyboardPreview.classList.contains('d-none')) {
    return;
  }

  showKeyboardPreview(true);
}

function adjustKeyboardSize(delta) {
  if (!keyboardPreview) {
    return;
  }

  const currentWidth = keyboardPreview.getBoundingClientRect().width;
  const nextWidth = Math.min(Math.max(280, currentWidth + delta), window.innerWidth - 24);
  keyboardPreview.style.width = `${nextWidth}px`;
  showKeyboardPreview(!keyboardPreview.classList.contains('d-none'));
}

if (isIndicLanguage) {
  restoreKeyboardPreview();
  attachKeyboardDrag();

  if (keyboardToggleBtn) {
    keyboardToggleBtn.addEventListener('click', () => {
      showKeyboardPreview(keyboardPreview.classList.contains('d-none'));
    });
  }

  if (keyboardCloseBtn) {
    keyboardCloseBtn.addEventListener('click', () => {
      showKeyboardPreview(false);
    });
  }

  if (keyboardSizeUp) {
    keyboardSizeUp.addEventListener('click', () => adjustKeyboardSize(60));
  }

  if (keyboardSizeDown) {
    keyboardSizeDown.addEventListener('click', () => adjustKeyboardSize(-60));
  }

  window.addEventListener('mouseup', persistKeyboardPreviewState);
}

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

  const typed = normalizeLineEndings(document.getElementById('typingArea').value);
  const original = normalizeLineEndings(originalParagraph);
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

function normalizeLineEndings(text) {
  return String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
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
    container.className = 'row flex-column flex-md-row h-100';
    para.style.display = 'block';
    para.className = 'col-12 mb-2 h-50';
    typing.className = 'col-12 h-50';
  } else if (type === 'single') {
    para.style.display = 'none';
    typing.className = 'col-12 h-100';
  }

  document.getElementById('typingArea').focus();
}
</script>

<script src="assets/js/remington-keyboard.js"></script>

<script>
initRemingtonTyping('<?php echo htmlspecialchars($language, ENT_QUOTES); ?>');
</script>

</body>
</html>
