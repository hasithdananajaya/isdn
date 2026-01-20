<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

require_any_role(['customer']);
$u = current_user();

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
  header('Location: orders.php');
  exit;
}

$r = mysqli_query($conn, "SELECT * FROM orders WHERE id = $orderId AND customer_id = " . (int)$u['id'] . " LIMIT 1");
if (!$r || mysqli_num_rows($r) !== 1) {
  header('Location: orders.php');
  exit;
}
$order = mysqli_fetch_assoc($r);

$currency = currency_get();
$total = (float)$order['total'];
$totalLKR = $total * 320;

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_payment') {
  $cardNumber = preg_replace('/\s+/', '', (string)($_POST['card_number'] ?? ''));
  $cardName = esc($conn, $_POST['card_name'] ?? '');
  $expiry = esc($conn, $_POST['expiry'] ?? '');
  $cvv = (string)($_POST['cvv'] ?? '');
  $amount = (float)($_POST['amount'] ?? 0);

  if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
    $err = 'Invalid card number.';
  } elseif ($cardName === '') {
    $err = 'Cardholder name is required.';
  } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
    $err = 'Invalid expiry date format (MM/YY).';
  } elseif (strlen($cvv) < 3 || strlen($cvv) > 4) {
    $err = 'Invalid CVV.';
  } elseif (abs($amount - $total) > 0.01) {
    $err = 'Payment amount mismatch.';
  } else {
    $paymentId = 'PAY_' . time() . '_' . mt_rand(1000, 9999);
    $paymentMethod = 'card';
    $paymentStatus = 'completed';
    
    $logFile = __DIR__ . '/logs/payments.log';
    @mkdir(dirname($logFile), 0777, true);
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " | Order #$orderId | Amount: $amount | Method: $paymentMethod | Status: $paymentStatus | ID: $paymentId\n", FILE_APPEND);
    
    mysqli_query($conn, "UPDATE orders SET status = 'dispatched' WHERE id = $orderId AND status = 'pending' LIMIT 1");
    
    flash_set('msg', "Payment successful! Payment ID: $paymentId");
    header('Location: order-confirmation.php?order_id=' . $orderId . '&paid=1');
    exit;
  }
}
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment | ISDN</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="ecommerce-styles.css">
  <style>
    .payment-card {
      background: linear-gradient(135deg, rgba(16,185,129,.1), rgba(5,150,105,.1));
      border: 2px solid rgba(16,185,129,.2);
      border-radius: var(--radius-xl);
      padding: 20px;
      margin: 20px 0;
    }
    .card-input-group {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 12px;
    }
    .demo-info {
      background: rgba(16,185,129,.1);
      border-left: 4px solid var(--success);
      padding: 12px;
      border-radius: 12px;
      margin-bottom: 16px;
    }
    .demo-info strong { color: var(--success); }
  </style>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>
  <main class="main">
    <div class="container">
      <div class="section-head">
        <h2><?php echo icon('credit-card'); ?> Secure Payment</h2>
        <span class="badge-status status-delivered">Order #<?php echo (int)$orderId; ?></span>
      </div>

      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

      <div class="grid cols-2" style="align-items:start">
        <section class="card pad lift" style="animation-delay:.06s">
          <div class="section-head">
            <h2><?php echo icon('lock'); ?> Payment Details</h2>
            <span class="muted">Demo payment gateway</span>
          </div>

          <div class="demo-info">
            <strong><?php echo icon('circle-info'); ?> Demo Payment</strong>
            <div class="muted" style="margin-top:6px; font-size:13px">
              Use any card number (e.g., 4242 4242 4242 4242), any future expiry (e.g., 12/25), and any CVV (e.g., 123).
            </div>
          </div>

          <form class="form" method="post" id="paymentForm">
            <input type="hidden" name="action" value="process_payment">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars((string)$total); ?>">

            <div class="field">
              <label>Card Number</label>
              <input class="input" name="card_number" type="text" placeholder="4242 4242 4242 4242" maxlength="19" required
                     oninput="this.value = this.value.replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim()">
            </div>

            <div class="field">
              <label>Cardholder Name</label>
              <input class="input" name="card_name" type="text" placeholder="John Doe" required>
            </div>

            <div class="card-input-group">
              <div class="field">
                <label>Expiry (MM/YY)</label>
                <input class="input" name="expiry" type="text" placeholder="12/25" maxlength="5" required
                       oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1/$2')">
              </div>
              <div class="field">
                <label>CVV</label>
                <input class="input" name="cvv" type="text" placeholder="123" maxlength="4" required
                       oninput="this.value = this.value.replace(/\D/g, '')">
              </div>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%">
              <?php echo icon('credit-card'); ?> Pay <span data-price="<?php echo htmlspecialchars((string)$total); ?>"><?php echo htmlspecialchars(price_label($total, $currency)); ?></span>
            </button>
          </form>
        </section>

        <aside class="card pad lift" style="animation-delay:.12s">
          <div class="section-head">
            <h2><?php echo icon('receipt'); ?> Order Summary</h2>
            <span class="muted">Payment details</span>
          </div>

          <div class="payment-card">
            <div style="font-weight:1000; font-size:24px; margin-bottom:8px">
              <span data-price="<?php echo htmlspecialchars((string)$total); ?>"><?php echo htmlspecialchars(price_label($total, $currency)); ?></span>
            </div>
            <div class="muted">
              <?php if ($currency === 'USD'): ?>
                LKR equivalent: <strong data-price="<?php echo htmlspecialchars((string)$total); ?>" data-show-lkr="1">LKR <?php echo number_format($totalLKR, 2); ?></strong>
              <?php else: ?>
                USD base: <strong>$<?php echo number_format($total, 2); ?></strong>
              <?php endif; ?>
            </div>
            <div class="muted" style="margin-top:8px; font-size:12px">
              Currency: <strong><?php echo htmlspecialchars($currency); ?></strong> (Selected in navbar)
            </div>
          </div>

          <div class="timeline" style="margin-top:16px">
            <div class="timeline-item done">
              <div class="timeline-dot"><?php echo icon('shopping-cart'); ?></div>
              <div>
                <div style="font-weight:1000">Order Placed</div>
                <div class="muted">Order #<?php echo (int)$orderId; ?></div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"><?php echo icon('credit-card'); ?></div>
              <div>
                <div style="font-weight:1000">Payment</div>
                <div class="muted">Secure demo gateway</div>
              </div>
            </div>
          </div>

          <div style="margin-top:20px; padding:14px; background:var(--card2); border-radius:14px; border:1px solid var(--border)">
            <div style="font-weight:1000; margin-bottom:8px"><?php echo icon('shield-halved'); ?> Secure Payment</div>
            <div class="muted" style="font-size:13px">
              Your payment information is encrypted and processed securely. We use industry-standard security protocols.
            </div>
          </div>

          <a class="btn btn-ghost" href="orders.php" style="width:100%; margin-top:16px">
            <?php echo icon('arrow-left'); ?> Back to Orders
          </a>
        </aside>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>
