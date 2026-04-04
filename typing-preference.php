<?php
require_once __DIR__ . '/includes/init.php';
/** @var mysqli $conn */

if (!isStudentLoggedIn()) {
    syncGuestAttemptsWithClient($_GET['guest_attempts_used'] ?? null, $conn);
}

$access = getAccessContext($conn);
$message = getFlash('auth_message');

if ($access['is_logged_in'] && !$access['has_active_plan']) {
    setFlash('auth_message', 'Activate your plan to continue with unlimited typing tests.');
    redirect('payment.php');
}

$remainingTests = $access['guest_tests_remaining'];

if (!$access['is_logged_in'] && $remainingTests <= 0) {
    setFlash('auth_message', 'Your 5 free guest tests are finished. Create an account to continue and activate 30-day access.');
    redirect('account/register.php');
}

$languages = [];
$schemaReady = dbTableExists($conn, 'languages') && dbTableExists($conn, 'exam_types') && dbTableExists($conn, 'passages');

if ($schemaReady) {
    $result = $conn->query("SELECT id, name FROM languages ORDER BY name ASC");
    while ($row = $result->fetch_assoc()) {
        $languages[] = $row;
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/style.css">

<section class="container my-5 min-vh-100">
  <div class="row justify-content-center">
    <div class="col-xl-10">
      <?php if (!empty($message)) echo successAlert(htmlspecialchars($message)); ?>

      <?php if (!$schemaReady) { ?>
        <?php echo warningAlert('Typing preference tables are missing. Import config/typing_preference_schema.sql to enable the dynamic preference system.'); ?>
      <?php } ?>

      <div class="card border-1 border-dark shadow-sm">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
              <h3 class="mb-2">Select Typing Preferences</h3>
              <p class="text-muted mb-0">
                <?php if ($access['is_logged_in']) { ?>
                  Your plan is active. Enjoy unlimited typing tests.
                <?php } else { ?>
                  Guest mode gives you <?php echo GUEST_TEST_LIMIT; ?> total free tests. Sign up to unlock unlimited access.
                <?php } ?>
              </p>
            </div>

            <div class="border rounded-3 px-4 py-3 bg-light">
              <?php if ($access['is_logged_in']) { ?>
                <div class="fw-semibold">Unlimited access active</div>
                <small class="text-muted">Plan expires on <?php echo htmlspecialchars($access['student']['expiry_date'] ?? 'N/A'); ?></small>
              <?php } else { ?>
                <div class="fw-semibold">Tests remaining: <span id="guestTestsRemaining"><?php echo $remainingTests; ?></span> / <?php echo GUEST_TEST_LIMIT; ?></div>
                <small class="text-muted">After <?php echo GUEST_TEST_LIMIT; ?> tests, you will be redirected to sign up.</small>
              <?php } ?>
            </div>
          </div>

          <form id="typingPreferenceForm" action="typing-test.php" method="GET">
            <input type="hidden" name="language" id="languageName" value="">
            <input type="hidden" name="exam_type" id="examTypeName" value="">
            <input type="hidden" name="time" id="timeSeconds" value="">
            <input type="hidden" name="guest_attempts_used" id="guestAttemptsUsed" value="<?php echo (int) $access['guest_attempts_used']; ?>">

            <div class="row g-3">
              <div class="col-md-2">
                <label class="form-label">Language</label>
                <select name="language_id" id="languageSelect" class="form-select border-dark" <?php echo $schemaReady ? '' : 'disabled'; ?> required>
                  <option value="">Select</option>
                  <?php foreach ($languages as $language) { ?>
                    <option value="<?php echo (int) $language['id']; ?>">
                      <?php echo htmlspecialchars($language['name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label">Exam Type</label>
                <select name="exam_type_id" id="examTypeSelect" class="form-select border-dark" disabled required>
                  <option value="">Select</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Passage</label>
                <select name="paragraph" id="passageSelect" class="form-select border-dark" disabled required>
                  <option value="">Select</option>
                </select>
                <div id="passageHelp" class="form-text">Choose a passage that matches your selected language and exam.</div>
              </div>

              <div class="col-md-2">
                <label class="form-label">Time (minutes)</label>
                <input type="number" id="timeMinutes" class="form-control border-dark" min="1" max="30" value="5" <?php echo $schemaReady ? '' : 'disabled'; ?> required>
                <div class="form-text">Auto-filled from exam type, but you can edit it.</div>
              </div>

              <div class="col-md-3">
                <label class="form-label">Backspace</label>
                <select name="backspace" id="backspaceSelect" class="form-select border-dark">
                  <option value="on" selected>On</option>
                  <option value="off">Off</option>
                </select>
                <div class="form-text">Control whether backspace is allowed during the test.</div>
              </div>
            </div>

            <div id="loadingState" class="d-none mt-4 text-muted">
              <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
              Loading options...
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-4">
              <button type="submit" id="startTestBtn" class="btn btn-dark px-5 py-2" <?php echo $schemaReady ? '' : 'disabled'; ?>>Start Test</button>

              <?php if (!$access['is_logged_in']) { ?>
                <a href="account/register.php" class="btn btn-outline-dark px-5 py-2">Sign Up for Unlimited Access</a>
              <?php } ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
const isLoggedIn = <?php echo $access['is_logged_in'] ? 'true' : 'false'; ?>;
const serverRemaining = <?php echo (int) $remainingTests; ?>;
const schemaReady = <?php echo $schemaReady ? 'true' : 'false'; ?>;
const languageSelect = document.getElementById('languageSelect');
const examTypeSelect = document.getElementById('examTypeSelect');
const passageSelect = document.getElementById('passageSelect');
const timeMinutesInput = document.getElementById('timeMinutes');
const timeSecondsInput = document.getElementById('timeSeconds');
const languageNameInput = document.getElementById('languageName');
const examTypeNameInput = document.getElementById('examTypeName');
const loadingState = document.getElementById('loadingState');
const passageHelp = document.getElementById('passageHelp');
const typingPreferenceForm = document.getElementById('typingPreferenceForm');
const startTestBtn = document.getElementById('startTestBtn');
const guestTestsRemainingLabel = document.getElementById('guestTestsRemaining');
const guestAttemptsUsedInput = document.getElementById('guestAttemptsUsed');
let isCountdownRunning = false;

function updateSubmissionFields() {
  const selectedLanguage = languageSelect.options[languageSelect.selectedIndex];
  const selectedExamType = examTypeSelect.options[examTypeSelect.selectedIndex];

  languageNameInput.value = selectedLanguage?.text || '';
  examTypeNameInput.value = selectedExamType?.dataset.name || selectedExamType?.text || '';
  timeSecondsInput.value = String(Math.max(1, Number(timeMinutesInput.value || 1)) * 60);

  if (guestAttemptsUsedInput && !isLoggedIn) {
    guestAttemptsUsedInput.value = String(Number(localStorage.getItem('guestTestAttempts') || '0'));
  }
}

function beginStartCountdown() {
  if (!startTestBtn || isCountdownRunning) {
    return;
  }

  isCountdownRunning = true;
  let countdown = 5;
  startTestBtn.disabled = true;
  startTestBtn.textContent = `Starting in ${countdown}`;

  const countdownTimer = window.setInterval(() => {
    countdown--;

    if (countdown > 0) {
      startTestBtn.textContent = `Starting in ${countdown}`;
      return;
    }

    window.clearInterval(countdownTimer);
    startTestBtn.textContent = 'Opening Test...';
    window.setTimeout(() => typingPreferenceForm.submit(), 300);
  }, 1000);
}

function setLoading(isLoading) {
  loadingState.classList.toggle('d-none', !isLoading);
}

function resetSelect(select, placeholder) {
  select.innerHTML = `<option value="">${placeholder}</option>`;
  select.disabled = true;
}

async function fetchJson(url) {
  const response = await fetch(url, {
    headers: {
      'Accept': 'application/json'
    }
  });
  const data = await response.json();

  if (!response.ok || !data.success) {
    throw new Error(data.message || 'Unable to load data.');
  }

  return data;
}

async function loadExamTypes(languageId) {
  resetSelect(examTypeSelect, 'Loading exam types...');
  resetSelect(passageSelect, 'Select exam type first');
  passageHelp.textContent = 'Choose a passage that matches your selected language and exam.';
  setLoading(true);

  try {
    const data = await fetchJson(`get_exam_types.php?language_id=${encodeURIComponent(languageId)}`);
    resetSelect(examTypeSelect, data.exam_types.length ? 'Select exam type' : 'No exam type available');

    data.exam_types.forEach((examType) => {
      const option = document.createElement('option');
      option.value = examType.id;
      option.textContent = `${examType.name} (${examType.wpm} WPM)`;
      option.dataset.name = examType.name;
      option.dataset.timeLimit = examType.time_limit;
      examTypeSelect.appendChild(option);
    });

    examTypeSelect.disabled = data.exam_types.length === 0;
  } finally {
    setLoading(false);
  }
}

async function loadPassages(languageId, examTypeId) {
  resetSelect(passageSelect, 'Loading passages...');
  passageHelp.textContent = 'Loading passages...';
  setLoading(true);

  try {
    const data = await fetchJson(`get_passages.php?language_id=${encodeURIComponent(languageId)}&exam_type_id=${encodeURIComponent(examTypeId)}`);
    resetSelect(passageSelect, data.passages.length ? 'Select passage' : 'No passage available');

    data.passages.forEach((passage) => {
      const option = document.createElement('option');
      option.value = passage.id;
      option.textContent = passage.label;
      passageSelect.appendChild(option);
    });

    passageSelect.disabled = data.passages.length === 0;
    passageHelp.textContent = data.passages.length ? 'Choose a passage that matches your selected language and exam.' : 'No passage available for this language and exam type.';
  } finally {
    setLoading(false);
  }
}

if (!isLoggedIn) {
  const localUsed = Number(localStorage.getItem('guestTestAttempts') || '0');
  const serverUsed = <?php echo GUEST_TEST_LIMIT; ?> - serverRemaining;
  const syncedUsed = Math.max(localUsed, serverUsed);
  const syncedRemaining = Math.max(0, <?php echo GUEST_TEST_LIMIT; ?> - syncedUsed);

  localStorage.setItem('guestTestAttempts', String(syncedUsed));
  localStorage.setItem('guestTestsRemaining', String(syncedRemaining));

  if (guestAttemptsUsedInput) {
    guestAttemptsUsedInput.value = String(syncedUsed);
  }

  if (guestTestsRemainingLabel) {
    guestTestsRemainingLabel.textContent = String(syncedRemaining);
  }
}

if (schemaReady) {
  languageSelect.addEventListener('change', async () => {
    const selectedOption = languageSelect.options[languageSelect.selectedIndex];
    languageNameInput.value = selectedOption?.text || '';
    examTypeNameInput.value = '';
    timeMinutesInput.value = '5';
    timeSecondsInput.value = String(5 * 60);

    if (!languageSelect.value) {
      resetSelect(examTypeSelect, 'Select language first');
      resetSelect(passageSelect, 'Select exam type first');
      return;
    }

    await loadExamTypes(languageSelect.value);
  });

  examTypeSelect.addEventListener('change', async () => {
    const selectedOption = examTypeSelect.options[examTypeSelect.selectedIndex];
    examTypeNameInput.value = selectedOption?.dataset.name || '';

    if (selectedOption?.dataset.timeLimit) {
      const minutes = Math.max(1, Math.round(Number(selectedOption.dataset.timeLimit) / 60));
      timeMinutesInput.value = String(minutes);
      timeSecondsInput.value = String(minutes * 60);
    }

    if (!examTypeSelect.value || !languageSelect.value) {
      resetSelect(passageSelect, 'Select exam type first');
      return;
    }

    await loadPassages(languageSelect.value, examTypeSelect.value);
  });

  timeMinutesInput.addEventListener('input', () => {
    const minutes = Math.max(1, Number(timeMinutesInput.value || 1));
    timeSecondsInput.value = String(Math.round(minutes * 60));
  });

  typingPreferenceForm.addEventListener('submit', (event) => {
    event.preventDefault();
    updateSubmissionFields();

    if (!languageSelect.value || !examTypeSelect.value || !passageSelect.value) {
      alert('Please select language, exam type, and passage first.');
      return;
    }

    beginStartCountdown();
  });
}
</script>

<?php include 'includes/footer.php'; ?>
