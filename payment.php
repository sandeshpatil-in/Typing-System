<?php
require_once __DIR__ . '/includes/init.php';

if (!isStudentLoggedIn()) {
    setFlash('auth_message', 'Create an account or log in to activate your paid plan.');
    redirect('account/register.php');
}

$student = syncStudentPlanStatus($conn, getStudentId());
$message = getFlash('auth_message');
$hasActivePlan = hasActivePlan($student);
$paymentConfigured = !empty(RAZORPAY_KEY_ID) && !empty(RAZORPAY_KEY_SECRET);
$latestPlan = $student ? getLatestPaidPlan($conn, (int) $student['id']) : null;
$paymentLabel = getPlanPaymentLabel($latestPlan);
$isRazorpayTestMode = str_starts_with(RAZORPAY_KEY_ID, 'rzp_test_');
$isRazorpayLiveMode = $paymentConfigured && str_starts_with(RAZORPAY_KEY_ID, 'rzp_live_');
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5 min-vh-100">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if (!empty($message)) echo successAlert(htmlspecialchars($message)); ?>

            <div class="card border-dark shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center g-4">
                        <div class="col-md-7">
                            <span class="badge text-bg-dark mb-3">Freemium Plan Upgrade</span>
                            <?php if ($paymentConfigured && $isRazorpayTestMode) { ?>
                                <span class="badge text-bg-warning text-dark mb-3 ms-2">Razorpay Test Mode</span>
                            <?php } elseif ($isRazorpayLiveMode) { ?>
                                <span class="badge text-bg-success mb-3 ms-2">Razorpay Live Mode</span>
                            <?php } ?>
                            <h2 class="mb-3"><?php echo htmlspecialchars(PLAN_NAME); ?></h2>
                            <p class="text-muted">Unlock unlimited typing tests, keep your progress, and access the full typing system for the next <?php echo PLAN_DURATION_DAYS; ?> days.</p>

                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item px-0">Unlimited typing tests during your active plan</li>
                                <li class="list-group-item px-0">Secure checkout with Razorpay</li>
                                <li class="list-group-item px-0">Automatic Razorpay activation for <?php echo PLAN_DURATION_DAYS; ?> days</li>
                                <li class="list-group-item px-0">Admin can manually activate Manual Pay payments for the same <?php echo PLAN_DURATION_DAYS; ?>-day access</li>
                            </ul>
                        </div>

                        <div class="col-md-5">
                            <div class="border rounded-3 p-4 bg-light">
                                <h3 class="mb-1">Rs. <?php echo number_format(PLAN_PRICE, 2); ?></h3>
                                <p class="text-muted mb-4">Per <?php echo PLAN_DURATION_DAYS; ?> days</p>

                                <?php if ($hasActivePlan) { ?>
                                    <?php echo successAlert('Your plan is active until ' . htmlspecialchars($student['expiry_date']) . '.'); ?>
                                    <a href="account/dashboard.php" class="btn btn-dark w-100">Go to Dashboard</a>
                                <?php } elseif (!$paymentConfigured) { ?>
                                    <?php echo warningAlert('Add your Razorpay key ID and key secret in the server environment to enable payments.'); ?>
                                    <button class="btn btn-dark w-100" disabled>Razorpay Not Configured</button>
                                <?php } else { ?>
                                    <?php if ($isRazorpayLiveMode) { ?>
                                        <div class="alert alert-success small py-2">
                                            Live payments are enabled. Completed payments will charge real money and activate the plan automatically.
                                        </div>
                                    <?php } ?>
                                    <button id="payNowBtn" class="btn btn-dark w-100">Pay with Razorpay</button>
                                    <p class="small text-muted mt-3 mb-0">You will be redirected back here if payment is not completed.</p>
                                <?php } ?>

                                <?php if (!$hasActivePlan) { ?>
                                    <div class="alert alert-light border mt-3 mb-0 small">
                                        <strong>Paid by Manual Pay?</strong> Ask admin to activate your account from the student table.
                                        <?php if ($latestPlan) { ?>
                                            <div class="mt-1 text-muted">Last payment record: <?php echo htmlspecialchars($paymentLabel); ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$hasActivePlan && $paymentConfigured) { ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const payButton = document.getElementById('payNowBtn');

function setPayButtonState(disabled, label) {
  if (!payButton) {
    return;
  }

  payButton.disabled = disabled;
  payButton.textContent = label;
}

if (payButton) {
  payButton.addEventListener('click', async () => {
    setPayButtonState(true, 'Preparing payment...');

    try {
      const response = await fetch('api/create-order.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          csrf_token: '<?php echo htmlspecialchars(csrfToken()); ?>'
        })
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Unable to create payment order.');
      }

      const options = {
        key: data.key_id,
        amount: data.amount,
        currency: data.currency,
        name: data.name,
        description: data.description,
        order_id: data.order_id,
        prefill: data.prefill,
        theme: { color: '#212529' },
        handler: async function (paymentResponse) {
          setPayButtonState(true, 'Verifying payment...');

          const verifyResponse = await fetch('api/verify-payment.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
              csrf_token: '<?php echo htmlspecialchars(csrfToken()); ?>',
              razorpay_order_id: paymentResponse.razorpay_order_id,
              razorpay_payment_id: paymentResponse.razorpay_payment_id,
              razorpay_signature: paymentResponse.razorpay_signature
            })
          });

          const verifyData = await verifyResponse.json();

          if (!verifyResponse.ok || !verifyData.success) {
            alert(verifyData.message || 'Payment verification failed.');
            setPayButtonState(false, 'Pay with Razorpay');
            return;
          }

          window.location.href = verifyData.redirect;
        },
        modal: {
          ondismiss: function () {
            setPayButtonState(false, 'Pay with Razorpay');
          }
        }
      };

      const razorpay = new Razorpay(options);
      razorpay.on('payment.failed', function (response) {
        const reason = response?.error?.description || response?.error?.reason || 'Payment failed. Please try again.';
        alert(reason);
        setPayButtonState(false, 'Pay with Razorpay');
      });
      razorpay.open();
    } catch (error) {
      alert(error.message);
      setPayButtonState(false, 'Pay with Razorpay');
    }
  });
}
</script>
<?php } ?>

<?php include 'includes/footer.php'; ?>
