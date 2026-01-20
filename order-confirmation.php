<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

require_any_role(['customer']);
$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
  header('Location: orders.php');
  exit;
}
$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order Confirmed | ISDN</title>
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
      <?php
      $paid = isset($_GET['paid']) && (int)$_GET['paid'] === 1;
      ?>
      <section class="card pad lift" style="max-width:820px;margin:0 auto;animation-delay:.08s">
        <div class="section-head">
          <h2><?php echo icon('circle-check'); ?> <?php echo $paid ? 'Payment Successful!' : 'Order placed successfully'; ?></h2>
          <span class="pill"><?php echo icon('hashtag'); ?> #<?php echo (int)$orderId; ?></span>
        </div>
        <?php if ($paid): ?>
          <div class="alert success" style="margin-bottom:16px">
            <strong><?php echo icon('check-circle'); ?> Payment completed successfully!</strong>
            <div class="muted" style="margin-top:6px">Your order has been confirmed and will be dispatched soon.</div>
          </div>
        <?php else: ?>
          <p class="muted">Thank you. Your order is now <strong>pending</strong> and will be assigned for dispatch.</p>
        <?php endif; ?>
        <div class="product-actions" style="margin-top:14px">
          <a class="btn btn-primary" href="orders.php"><?php echo icon('clipboard-list'); ?> View my orders</a>
          <a class="btn btn-ghost" href="track-order.php?order_id=<?php echo (int)$orderId; ?>"><?php echo icon('location-dot'); ?> Track order</a>
          <a class="btn btn-ghost" href="generate-invoice.php?order_id=<?php echo (int)$orderId; ?>"><?php echo icon('file-invoice'); ?> Download invoice</a>
        </div>
      </section>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>

