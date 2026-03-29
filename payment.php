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
                            <h2 class="mb-3"><?php echo htmlspecialchars(PLAN_NAME); ?></h2>
                            <p class="text-muted">Unlock unlimited typing tests, keep your progress, and access the dashboard for the next <?php echo PLAN_DURATION_DAYS; ?> days.</p>

                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item px-0">Unlimited typing tests during your active plan</li>
                                <li class="list-group-item px-0">Secure checkout with Razorpay</li>
                                <li class="list-group-item px-0">Automatic plan activation for <?php echo PLAN_DURATION_DAYS; ?> days</li>
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
                                    <button id="payNowBtn" class="btn btn-dark w-100">Pay with Razorpay</button>
                                    <p class="small text-muted mt-3 mb-0">You will be redirected back here if payment is not completed.</p>
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

if (payButton) {
  payButton.addEventListener('click', async () => {
    payButton.disabled = true;
    payButton.textContent = 'Preparing payment...';

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
            payButton.disabled = false;
            payButton.textContent = 'Pay with Razorpay';
            return;
          }

          window.location.href = verifyData.redirect;
        },
        modal: {
          ondismiss: function () {
            payButton.disabled = false;
            payButton.textContent = 'Pay with Razorpay';
          }
        }
      };

      const razorpay = new Razorpay(options);
      razorpay.open();
    } catch (error) {
      alert(error.message);
      payButton.disabled = false;
      payButton.textContent = 'Pay with Razorpay';
    }
  });
}
</script>
<?php } ?>

<?php include 'includes/footer.php'; ?>
