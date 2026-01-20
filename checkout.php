<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';
require_once __DIR__ . '/send-email.php';

require_any_role(['customer']);
$u = current_user();
$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

$cart = cart_get();
if (!$cart) {
  header('Location: cart.php');
  exit;
}

$ids = array_map('intval', array_keys($cart));
$idList = implode(',', $ids);
$items = [];
$subtotal = 0.0;
$r = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($idList)");
if ($r) {
  while ($p = mysqli_fetch_assoc($r)) {
    $pid = (int)$p['id'];
    $qty = (int)($cart[$pid] ?? 0);
    if ($qty <= 0) continue;
    $line = (float)$p['price'] * $qty;
    $subtotal += $line;
    $p['_qty'] = $qty;
    $p['_line'] = $line;
    $items[] = $p;
  }
}
if (!$items) {
  flash_set('err', 'Your cart items could not be loaded. Please try again.');
  header('Location: cart.php');
  exit;
}

$tax = $subtotal * 0.05;
$shipping = 3.00;
$total = $subtotal + $tax + $shipping;
$currency = currency_get();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
  foreach ($items as $p) {
    if ((int)$p['stock'] < (int)$p['_qty']) {
      flash_set('err', 'Insufficient stock for: ' . $p['name']);
      header('Location: checkout.php');
      exit;
    }
  }

  mysqli_begin_transaction($conn);
  $ok = true;
  $customerId = (int)$u['id'];

  $sql = "INSERT INTO orders (customer_id, total, status) VALUES ($customerId, " . number_format($total, 2, '.', '') . ", 'pending')";
  if (!mysqli_query($conn, $sql)) $ok = false;
  $orderId = $ok ? (int)mysqli_insert_id($conn) : 0;

  if ($ok && $orderId > 0) {
    foreach ($items as $p) {
      $pid = (int)$p['id'];
      $qty = (int)$p['_qty'];
      $price = (float)$p['price'];
      $lineSql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                  VALUES ($orderId, $pid, $qty, " . number_format($price, 2, '.', '') . ")";
      if (!mysqli_query($conn, $lineSql)) { $ok = false; break; }

      $upd = "UPDATE products SET stock = stock - $qty WHERE id = $pid AND stock >= $qty";
      if (!mysqli_query($conn, $upd) || mysqli_affected_rows($conn) !== 1) { $ok = false; break; }
    }
  }

  if ($ok && $orderId > 0) {
    $d = "INSERT INTO deliveries (order_id, rdc_staff_id, status, assigned_date, delivered_date)
          VALUES ($orderId, NULL, 'pending', NULL, NULL)";
    if (!mysqli_query($conn, $d)) $ok = false;
  }

  if ($ok) {
    mysqli_commit($conn);
    $_SESSION['cart'] = [];

    if (!empty($u['email']) && filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
      $to = $u['email'];
      $emailCurrency = currency_get();
      $totalDisplay = $emailCurrency === 'LKR' ? 'LKR ' . number_format($total * 320, 2) : '$' . number_format($total, 2);
      $subject = "ISDN Order Confirmation #$orderId";
      $body = '<p>Dear <strong>' . htmlspecialchars($u['name']) . '</strong>,</p>'
        . '<p>Your order has been placed successfully. Order ID: <strong>#' . (int)$orderId . '</strong>.</p>'
        . '<p>Total (' . htmlspecialchars($emailCurrency) . '): <strong>' . htmlspecialchars($totalDisplay) . '</strong></p>'
        . '<p>Base amount (USD): <strong>$' . number_format($total, 2) . '</strong></p>'
        . '<p>You can download your invoice anytime from <strong>My Orders</strong>.</p>'
        . '<p>Thank you for choosing ISDN!</p>';
      $html = render_email_template("Order Confirmation #$orderId", $body);
      send_isdn_mail($to, $subject, $html);
    }

      header('Location: payment.php?order_id=' . $orderId);
      exit;
  } else {
    mysqli_rollback($conn);
    flash_set('err', 'Order failed due to a system error. Please try again.');
    header('Location: checkout.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout | ISDN</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="ecommerce-styles.css">
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>
  <main class="main">
    <div class="container">
      <div class="section-head">
        <h2><?php echo icon('credit-card'); ?> Checkout</h2>
        <span class="muted">Multi-step UI • stock validation • invoice ready</span>
      </div>

      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

      <div class="steps lift" style="animation-delay:.06s">
        <div class="step"><div class="k"><?php echo icon('cart-shopping'); ?> Step 1</div><div class="v">Review cart</div></div>
        <div class="step"><div class="k"><?php echo icon('location-dot'); ?> Step 2</div><div class="v">Delivery details</div></div>
        <div class="step"><div class="k"><?php echo icon('lock'); ?> Step 3</div><div class="v">Place order</div></div>
      </div>

      <div class="grid cols-2" style="margin-top:16px; align-items:start">
        <section class="card pad lift" style="animation-delay:.12s">
          <div class="section-head">
            <h2><?php echo icon('clipboard-list'); ?> Order review</h2>
            <span class="muted"><?php echo count($items); ?> items</span>
          </div>
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Line</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $p): ?>
                <tr>
                  <td>
                    <div style="font-weight:1000"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div class="muted" style="font-size:12px"><?php echo htmlspecialchars($p['rdc_location']); ?></div>
                  </td>
                  <td><span class="badge-status"><?php echo icon('hashtag'); ?> <?php echo (int)$p['_qty']; ?></span></td>
                  <td class="price" data-price="<?php echo htmlspecialchars((string)$p['_line']); ?>"><?php echo htmlspecialchars(price_label($p['_line'], $currency)); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>

        <aside class="card pad lift" style="animation-delay:.18s">
          <div class="section-head">
            <h2><?php echo icon('truck-fast'); ?> Delivery info</h2>
            <span class="muted">From RDCs</span>
          </div>
          <div class="timeline">
            <div class="timeline-item done">
              <div class="timeline-dot"><?php echo icon('user'); ?></div>
              <div>
                <div style="font-weight:1000"><?php echo htmlspecialchars($u['name']); ?></div>
                <div class="muted"><?php echo htmlspecialchars($u['email']); ?><?php echo $u['phone'] ? ' • ' . htmlspecialchars($u['phone']) : ''; ?></div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"><?php echo icon('route'); ?></div>
              <div>
                <div style="font-weight:1000">Tracking</div>
                <div class="muted">You can track after dispatch in <strong>Track Orders</strong>.</div>
              </div>
            </div>
          </div>

          <div class="cart-summary" style="margin-top:12px">
            <div class="summary-row"><span class="muted">Subtotal</span><strong class="price" data-price="<?php echo htmlspecialchars((string)$subtotal); ?>"><?php echo htmlspecialchars(price_label($subtotal, $currency)); ?></strong></div>
            <div class="summary-row"><span class="muted">Tax (5%)</span><strong class="price" data-price="<?php echo htmlspecialchars((string)$tax); ?>"><?php echo htmlspecialchars(price_label($tax, $currency)); ?></strong></div>
            <div class="summary-row"><span class="muted">Shipping</span><strong class="price" data-price="<?php echo htmlspecialchars((string)$shipping); ?>"><?php echo htmlspecialchars(price_label($shipping, $currency)); ?></strong></div>
            <div class="summary-row" style="padding-top:10px; border-top:1px solid var(--border)">
              <span style="font-weight:1000">Total</span>
              <strong class="price" data-price="<?php echo htmlspecialchars((string)$total); ?>"><?php echo htmlspecialchars(price_label($total, $currency)); ?></strong>
            </div>

            <form method="post" id="checkoutForm">
              <input type="hidden" name="action" value="place_order">
              <button class="btn btn-primary" type="submit"><?php echo icon('credit-card'); ?> Proceed to Payment</button>
            </form>
            <a class="btn btn-ghost" href="cart.php"><?php echo icon('arrow-left'); ?> Back to cart</a>
          </div>
        </aside>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>

