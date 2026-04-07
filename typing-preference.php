<?php
require_once __DIR__ . '/includes/init.php';
/** @var mysqli $conn */

if (!isStudentLoggedIn()) {
    syncGuestAttemptsWithClient($_GET['guest_attempts_used'] ?? null, $conn);
}

$access = getAccessContext($conn);
$message = getFlash('auth_message');
$remainingTests = $access['is_logged_in']
    ? (int) ($access['free_tests_remaining'] ?? 0)
    : (int) ($access['guest_tests_remaining'] ?? 0);

if ($access['is_logged_in'] && !$access['has_active_plan'] && $remainingTests <= 0) {
    setFlash('auth_message', 'Your 5 free tests are finished. Activate your plan to continue.');
    redirect('payment.php');
}

if (!$access['is_logged_in'] && $remainingTests <= 0) {
    setFlash('auth_message', 'Your 5 free tests are finished. Create an account to continue and activate 30-day access.');
    redirect('account/register.php');
}

$languages = [];
$schemaReady = dbTableExists($conn, 'languages') && dbTableExists($conn, 'exam_types') && dbTableExists($conn, 'passages');

if ($schemaReady) {
    ensureTypingLevelsForAllLanguages($conn);
    $result = $conn->query("SELECT id, name FROM languages ORDER BY name ASC");

    while ($result && ($row = $result->fetch_assoc())) {
        $languages[] = $row;
    }
}

$levelOptions = getTypingLevelDefinitions();
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
                  <?php if ($access['has_active_plan']) { ?>
                    Your plan is active. Enjoy unlimited typing tests.
                  <?php } else { ?>
                    You have <?php echo $remainingTests; ?> free tests remaining before plan activation is required.
                  <?php } ?>
                <?php } else { ?>
                  Guest mode gives you <?php echo GUEST_TEST_LIMIT; ?> total free tests. Sign up to unlock unlimited access.
                <?php } ?>
              </p>
            </div>

            <div class="border rounded-3 px-4 py-3 bg-light">
              <?php if ($access['is_logged_in']) { ?>
                <?php if ($access['has_active_plan']) { ?>
                  <div class="fw-semibold">Unlimited access active</div>
                  <small class="text-muted">Plan expires on <?php echo htmlspecialchars($access['student']['expiry_date'] ?? 'N/A'); ?></small>
                <?php } else { ?>
                  <div class="fw-semibold">Free tests remaining: <span id="guestTestsRemaining"><?php echo $remainingTests; ?></span> / <?php echo GUEST_TEST_LIMIT; ?></div>
                  <small class="text-muted">After <?php echo GUEST_TEST_LIMIT; ?> free tests, activate your plan to continue.</small>
                <?php } ?>
              <?php } else { ?>
                <div class="fw-semibold">Tests remaining: <span id="guestTestsRemaining"><?php echo $remainingTests; ?></span> / <?php echo GUEST_TEST_LIMIT; ?></div>
                <small class="text-muted">After <?php echo GUEST_TEST_LIMIT; ?> tests, you will be redirected to sign up.</small>
              <?php } ?>
            </div>
          </div>

          <form class="" id="typingPreferenceForm" action="typing-test.php" method="GET">
            <input type="hidden" name="language" id="languageName" value="">
            <input type="hidden" name="level" id="levelSlug" value="">
            <input type="hidden" name="exam_type" id="levelName" value="">
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

              <div class="col-md-3">
                <label class="form-label">Your Level</label>
                <select name="level_select" id="levelSelect" class="form-select border-dark" disabled required>
                  <option value="">Select language first</option>
                  <?php foreach ($levelOptions as $slug => $option) { ?>
                    <option value="<?php echo htmlspecialchars($slug); ?>" data-label="<?php echo htmlspecialchars($option['label']); ?>">
                      <?php echo htmlspecialchars($option['label']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Passage</label>
                <select name="paragraph" id="passageSelect" class="form-select border-dark" disabled required>
                  <option value="">Select level first</option>
                </select>
                <div id="passageHelp" class="form-text">Choose a passage that matches your selected language and level.</div>
              </div>

              <div class="col-md-2">
                <label class="form-label">Time Selection</label>
                <select id="timePresetSelect" class="form-select border-dark" <?php echo $schemaReady ? '' : 'disabled'; ?> required>
                  <option value="custom">Custom Time</option>
                  <option value="2">2 min</option>
                  <option value="5">5 min</option>
                  <option value="7" selected>7 min</option>
                  <option value="10">10 min</option>
                  <option value="12">12 min</option>
                  <option value="15">15 min</option>
                  <option value="20">20 min</option>
                </select>
                <div id="customTimeWrapper" class="mt-2 d-none">
                  <input type="number" id="customTimeMinutes" class="form-control border-dark" min="1" max="60" placeholder="Enter custom minutes">
                </div>
              </div>

              <div class="col-md-2">
                <label class="form-label">Backspace/Delete</label>
                <select name="delete_key" id="deleteKeySelect" class="form-select border-dark">
                  <option value="on" selected>On</option>
                  <option value="off">Off</option>
                </select>
                <div class="form-text">Turn delete and backspace keys on or off during the test.</div>
              </div>
            </div>

            <div id="loadingState" class="d-none mt-4 text-muted">
              <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
              Loading passages...
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-4">
              <button type="submit" id="startTestBtn" class="btn btn-dark px-5 py-2" <?php echo $schemaReady ? '' : 'disabled'; ?>>Start Test</button>

              <?php if (!$access['is_logged_in']) { ?>
                <a href="account/register.php" class="btn btn-outline-dark px-5 py-2">Sign Up for Unlimited Access</a>
              <?php } ?>
            </div>

            <div id="startCountdownPanel" class="alert alert-light border mt-3 d-none" role="status">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                  <div class="fw-semibold">Test will start in <span id="startCountdownValue">10</span> seconds.</div>
                  <div class="small text-muted">Use Skip to open the test now or Cancel to change your preferences.</div>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" id="skipCountdownBtn" class="btn btn-dark btn-sm">Skip</button>
                  <button type="button" id="cancelCountdownBtn" class="btn btn-outline-secondary btn-sm">Cancel</button>
                </div>
              </div>
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
const typingLevels = <?php echo json_encode(array_map(
  static function ($slug, $definition) {
    return [
      'slug' => $slug,
      'label' => $definition['label']
    ];
  },
  array_keys(getTypingLevelDefinitions()),
  getTypingLevelDefinitions()
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const languageSelect = document.getElementById('languageSelect');
const levelSelect = document.getElementById('levelSelect');
const passageSelect = document.getElementById('passageSelect');
const timePresetSelect = document.getElementById('timePresetSelect');
const customTimeWrapper = document.getElementById('customTimeWrapper');
const customTimeMinutes = document.getElementById('customTimeMinutes');
const timeSecondsInput = document.getElementById('timeSeconds');
const languageNameInput = document.getElementById('languageName');
const levelSlugInput = document.getElementById('levelSlug');
const levelNameInput = document.getElementById('levelName');
const loadingState = document.getElementById('loadingState');
const passageHelp = document.getElementById('passageHelp');
const typingPreferenceForm = document.getElementById('typingPreferenceForm');
const startTestBtn = document.getElementById('startTestBtn');
const startCountdownPanel = document.getElementById('startCountdownPanel');
const startCountdownValue = document.getElementById('startCountdownValue');
const skipCountdownBtn = document.getElementById('skipCountdownBtn');
const cancelCountdownBtn = document.getElementById('cancelCountdownBtn');
const guestTestsRemainingLabel = document.getElementById('guestTestsRemaining');
const guestAttemptsUsedInput = document.getElementById('guestAttemptsUsed');
let isCountdownRunning = false;
let countdownTimerId = null;

function updateSubmissionFields() {
  const selectedLanguage = languageSelect.options[languageSelect.selectedIndex];
  const selectedLevel = levelSelect.options[levelSelect.selectedIndex];

  languageNameInput.value = selectedLanguage?.text || '';
  levelSlugInput.value = selectedLevel?.value || '';
  levelNameInput.value = selectedLevel?.dataset.label || selectedLevel?.text || '';
  timeSecondsInput.value = String(getSelectedMinutes() * 60);

  if (guestAttemptsUsedInput && !isLoggedIn) {
    guestAttemptsUsedInput.value = String(Number(localStorage.getItem('guestTestAttempts') || '0'));
  }
}

function getSelectedMinutes() {
  if (timePresetSelect.value === 'custom') {
    return Math.max(1, Number(customTimeMinutes.value || 1));
  }

  return Math.max(1, Number(timePresetSelect.value || 5));
}

function toggleCustomTime() {
  const isCustom = timePresetSelect.value === 'custom';
  customTimeWrapper.classList.toggle('d-none', !isCustom);
  customTimeMinutes.required = isCustom;

  if (!isCustom) {
    customTimeMinutes.value = '';
  }

  timeSecondsInput.value = String(getSelectedMinutes() * 60);
}

function beginStartCountdown() {
  if (!startTestBtn || isCountdownRunning) {
    return;
  }

  isCountdownRunning = true;
  let countdown = 10;
  startTestBtn.disabled = true;
  startTestBtn.textContent = 'Preparing Test...';
  startCountdownPanel.classList.remove('d-none');
  startCountdownValue.textContent = String(countdown);

  countdownTimerId = window.setInterval(() => {
    countdown--;

    if (countdown > 0) {
      startCountdownValue.textContent = String(countdown);
      return;
    }

    window.clearInterval(countdownTimerId);
    countdownTimerId = null;
    startCountdownValue.textContent = '0';
    startTestBtn.textContent = 'Opening Test...';
    window.setTimeout(() => typingPreferenceForm.submit(), 300);
  }, 1000);
}

function resetStartCountdown() {
  if (countdownTimerId !== null) {
    window.clearInterval(countdownTimerId);
    countdownTimerId = null;
  }

  isCountdownRunning = false;
  startCountdownPanel.classList.add('d-none');
  startCountdownValue.textContent = '10';

  if (startTestBtn) {
    startTestBtn.disabled = false;
    startTestBtn.textContent = 'Start Test';
  }
}

function skipStartCountdown() {
  if (!isCountdownRunning) {
    return;
  }

  if (countdownTimerId !== null) {
    window.clearInterval(countdownTimerId);
    countdownTimerId = null;
  }

  startCountdownValue.textContent = '0';
  startTestBtn.textContent = 'Opening Test...';
  window.setTimeout(() => typingPreferenceForm.submit(), 150);
}

function setLoading(isLoading) {
  loadingState.classList.toggle('d-none', !isLoading);
}

function resetSelect(select, placeholder) {
  select.innerHTML = `<option value="">${placeholder}</option>`;
  select.disabled = true;
}

function populateLevels() {
  levelSelect.innerHTML = '<option value="">Select level</option>';

  typingLevels.forEach((level) => {
    const option = document.createElement('option');
    option.value = level.slug;
    option.dataset.label = level.label;
    option.textContent = level.label;
    levelSelect.appendChild(option);
  });

  levelSelect.disabled = false;
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

async function loadPassages(languageId, levelSlug) {
  resetSelect(passageSelect, 'Loading passages...');
  passageHelp.textContent = 'Loading passages...';
  setLoading(true);

  try {
    const data = await fetchJson(`get_passages.php?language_id=${encodeURIComponent(languageId)}&level=${encodeURIComponent(levelSlug)}`);
    resetSelect(passageSelect, data.passages.length ? 'Select passage' : 'No passage available');

    data.passages.forEach((passage) => {
      const option = document.createElement('option');
      option.value = passage.id;
      option.textContent = passage.label;
      passageSelect.appendChild(option);
    });

    passageSelect.disabled = data.passages.length === 0;
    passageHelp.textContent = data.passages.length
      ? 'Choose a passage that matches your selected language and level.'
      : 'No passage available for this language and level.';
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

toggleCustomTime();
updateSubmissionFields();

if (schemaReady) {
  languageSelect.addEventListener('change', () => {
    const selectedOption = languageSelect.options[languageSelect.selectedIndex];
    languageNameInput.value = selectedOption?.text || '';
    levelSlugInput.value = '';
    levelNameInput.value = '';
    resetSelect(passageSelect, 'Select level first');
    passageHelp.textContent = 'Choose a passage that matches your selected language and level.';

    if (!languageSelect.value) {
      resetSelect(levelSelect, 'Select language first');
      return;
    }

    populateLevels();
  });

  levelSelect.addEventListener('change', async () => {
    const selectedOption = levelSelect.options[levelSelect.selectedIndex];
    levelSlugInput.value = selectedOption?.value || '';
    levelNameInput.value = selectedOption?.dataset.label || '';

    if (!levelSelect.value || !languageSelect.value) {
      resetSelect(passageSelect, 'Select level first');
      return;
    }

    await loadPassages(languageSelect.value, levelSelect.value);
  });

  timePresetSelect.addEventListener('change', toggleCustomTime);
  customTimeMinutes.addEventListener('input', () => {
    timeSecondsInput.value = String(getSelectedMinutes() * 60);
  });
  skipCountdownBtn.addEventListener('click', skipStartCountdown);
  cancelCountdownBtn.addEventListener('click', resetStartCountdown);

  typingPreferenceForm.addEventListener('submit', (event) => {
    event.preventDefault();
    updateSubmissionFields();

    if (!languageSelect.value || !levelSelect.value || !passageSelect.value) {
      alert('Please select language, level, and passage first.');
      return;
    }

    if (timePresetSelect.value === 'custom' && !customTimeMinutes.value) {
      alert('Please enter custom time in minutes.');
      customTimeMinutes.focus();
      return;
    }

    beginStartCountdown();
  });
}
</script>

<?php include 'includes/footer.php'; ?>
