<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

require_login();
$u = current_user();
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
  header('Location: orders.php');
  exit;
}

$sql = "SELECT o.*, u.name customer_name, u.email customer_email, u.phone customer_phone
        FROM orders o
        LEFT JOIN users u ON o.customer_id = u.id
        WHERE o.id = $orderId";
if ($u['role'] === 'customer') {
  $sql .= " AND o.customer_id = " . (int)$u['id'];
}
$r = mysqli_query($conn, $sql . " LIMIT 1");
if (!$r || mysqli_num_rows($r) !== 1) {
  header('Location: orders.php');
  exit;
}
$order = mysqli_fetch_assoc($r);

$items = [];
$r = mysqli_query($conn, "SELECT oi.*, p.name product_name, p.category
                          FROM order_items oi
                          JOIN products p ON oi.product_id = p.id
                          WHERE oi.order_id = $orderId");
if ($r) while ($row = mysqli_fetch_assoc($r)) $items[] = $row;

$subtotal = 0.0;
foreach ($items as $item) {
  $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$tax = $subtotal * 0.05;
$shipping = 3.00;
$total = $subtotal + $tax + $shipping;

$currency = currency_get();
$showLKR = $currency === 'LKR';
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invoice #<?php echo (int)$orderId; ?> | ISDN</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="ecommerce-styles.css">
  <style>
    @media print {
      @page { margin: 1cm; }
      body { background: #fff; color: #000; }
      .navbar, .footer, .no-print { display: none !important; }
      .invoice { box-shadow: none; border: none; }
      .btn { display: none !important; }
      a { text-decoration: none; color: #000; }
    }
    .invoice { max-width: 900px; margin: 0 auto; }
    .invoice-header { border-bottom: 2px solid var(--border); padding-bottom: 20px; margin-bottom: 20px; }
    .invoice-meta { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>
  <main class="main">
    <div class="container">
      <div class="invoice card pad lift" style="animation-delay:.06s">
        <div class="invoice-header">
          <div class="section-head">
            <h2><?php echo icon('file-invoice'); ?> Invoice #<?php echo (int)$orderId; ?></h2>
            <div class="product-actions no-print">
              <button class="btn btn-primary" onclick="window.print()"><?php echo icon('print'); ?> Print / PDF</button>
              <a class="btn btn-ghost" href="orders.php"><?php echo icon('arrow-left'); ?> Back to orders</a>
            </div>
          </div>
        </div>

        <div class="invoice-meta">
          <div>
            <div style="font-weight:1000; font-size:18px; margin-bottom:8px">IslandLink Sales Distribution Network</div>
            <div class="muted">Colombo, Sri Lanka</div>
            <div class="muted">support@isdn.example</div>
            <div class="muted">+94 00 000 0000</div>
          </div>
          <div style="text-align:right">
            <div style="font-weight:1000; margin-bottom:8px">Invoice Details</div>
            <div class="muted">Invoice #: <strong><?php echo (int)$orderId; ?></strong></div>
            <div class="muted">Date: <strong><?php echo date('M d, Y', strtotime($order['order_date'])); ?></strong></div>
            <div class="muted">Status: <strong><?php echo ucfirst($order['status']); ?></strong></div>
          </div>
        </div>

        <div style="margin:20px 0">
          <div style="font-weight:1000; margin-bottom:8px">Bill To:</div>
          <div style="font-weight:1000"><?php echo htmlspecialchars($order['customer_name']); ?></div>
          <div class="muted"><?php echo htmlspecialchars($order['customer_email']); ?></div>
          <?php if ($order['customer_phone']): ?>
            <div class="muted"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
          <?php endif; ?>
        </div>

        <table style="margin:20px 0">
          <thead>
            <tr>
              <th>Item</th>
              <th>Category</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td style="font-weight:1000"><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td class="muted"><?php echo htmlspecialchars($item['category']); ?></td>
                <td><?php echo (int)$item['quantity']; ?></td>
                <td><?php echo $showLKR ? 'LKR ' . number_format((float)$item['price'] * 320, 2) : '$' . number_format((float)$item['price'], 2); ?></td>
                <td style="font-weight:1000"><?php echo $showLKR ? 'LKR ' . number_format((float)$item['price'] * (int)$item['quantity'] * 320, 2) : '$' . number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div style="margin-top:20px; padding-top:20px; border-top:2px solid var(--border)">
          <div style="display:flex; justify-content:flex-end">
            <div style="min-width:300px">
              <div class="summary-row">
                <span class="muted">Subtotal:</span>
                <strong><?php echo $showLKR ? 'LKR ' . number_format($subtotal * 320, 2) : '$' . number_format($subtotal, 2); ?></strong>
              </div>
              <div class="summary-row">
                <span class="muted">Tax (5%):</span>
                <strong><?php echo $showLKR ? 'LKR ' . number_format($tax * 320, 2) : '$' . number_format($tax, 2); ?></strong>
              </div>
              <div class="summary-row">
                <span class="muted">Shipping:</span>
                <strong><?php echo $showLKR ? 'LKR ' . number_format($shipping * 320, 2) : '$' . number_format($shipping, 2); ?></strong>
              </div>
              <div class="summary-row" style="padding-top:10px; border-top:1px solid var(--border); margin-top:10px">
                <span style="font-weight:1000; font-size:18px">Total:</span>
                <strong style="font-weight:1000; font-size:18px"><?php echo $showLKR ? 'LKR ' . number_format($total * 320, 2) : '$' . number_format($total, 2); ?></strong>
              </div>
              <?php if ($showLKR): ?>
                <div class="muted" style="margin-top:8px; font-size:12px; text-align:right">Base: $<?php echo number_format($total, 2); ?> USD</div>
              <?php else: ?>
                <div class="muted" style="margin-top:8px; font-size:12px; text-align:right">LKR equivalent: LKR <?php echo number_format($total * 320, 2); ?> (1 USD = 320 LKR)</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div style="margin-top:30px; padding:16px; background:var(--card2); border-radius:14px; border:1px solid var(--border); text-align:center">
          <div class="muted" style="font-size:12px">Thank you for your business!</div>
          <div class="muted" style="font-size:12px; margin-top:4px">This is an automated invoice generated by ISDN.</div>
        </div>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>
