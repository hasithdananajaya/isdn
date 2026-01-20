<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

require_login();
$u = current_user();
$role = $u['role'];

$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
  $oid = (int)($_POST['order_id'] ?? 0);
  $status = esc($conn, $_POST['status'] ?? '');
  if ($oid > 0 && in_array($status, ['pending','dispatched','delivered'], true)) {
    mysqli_query($conn, "UPDATE orders SET status = '$status' WHERE id = $oid LIMIT 1");
    mysqli_query($conn, "UPDATE deliveries SET status = '$status' WHERE order_id = $oid LIMIT 1");
    if ($status === 'dispatched') {
      mysqli_query($conn, "UPDATE deliveries SET assigned_date = NOW() WHERE order_id = $oid LIMIT 1");
    }
    if ($status === 'delivered') {
      mysqli_query($conn, "UPDATE deliveries SET delivered_date = NOW() WHERE order_id = $oid LIMIT 1");
    }
    flash_set('msg', 'Order status updated.');
  }
  header('Location: orders.php');
  exit;
}

$where = [];
if ($role === 'customer') {
  $where[] = "o.customer_id = " . (int)$u['id'];
} elseif ($role === 'rdc') {
  $rdcLoc = esc($conn, $u['rdc_location'] ?? '');
  if ($rdcLoc) {
    $where[] = "EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id AND p.rdc_location = '$rdcLoc')";
  }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$orders = [];
$sql = "SELECT o.*, u.name customer_name, u.email customer_email, u.phone customer_phone,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) item_count
        FROM orders o
        LEFT JOIN users u ON o.customer_id = u.id
        $whereSql
        ORDER BY o.order_date DESC";
$r = mysqli_query($conn, $sql);
if ($r) while ($row = mysqli_fetch_assoc($r)) $orders[] = $row;

$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orders | ISDN</title>
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
        <h2><?php echo icon('clipboard-list'); ?> <?php echo $role === 'customer' ? 'My Orders' : 'Order Management'; ?></h2>
        <span class="muted"><?php echo count($orders); ?> order(s)</span>
      </div>

      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

      <?php if (!$orders): ?>
        <section class="card pad lift" style="animation-delay:.06s">
          <strong>No orders found.</strong>
          <div class="muted" style="margin-top:6px"><?php echo $role === 'customer' ? 'Place your first order from the shop.' : 'No orders in the system yet.'; ?></div>
        </section>
      <?php else: ?>
        <section class="card pad lift" style="animation-delay:.06s">
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <?php if ($role !== 'customer'): ?><th>Customer</th><?php endif; ?>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <?php
                  $status = $o['status'];
                  $statusClass = 'status-' . $status;
                  $statusIcon = $status === 'pending' ? 'clock' : ($status === 'dispatched' ? 'truck-fast' : 'circle-check');
                ?>
                <tr>
                  <td><strong>#<?php echo (int)$o['id']; ?></strong></td>
                  <?php if ($role !== 'customer'): ?>
                    <td>
                      <div style="font-weight:1000"><?php echo htmlspecialchars($o['customer_name'] ?: 'N/A'); ?></div>
                      <div class="muted" style="font-size:12px"><?php echo htmlspecialchars($o['customer_email'] ?: ''); ?></div>
                    </td>
                  <?php endif; ?>
                  <td><span class="badge-status"><?php echo icon('hashtag'); ?> <?php echo (int)$o['item_count']; ?> items</span></td>
                  <td class="price" data-price="<?php echo htmlspecialchars((string)$o['total']); ?>"><?php echo htmlspecialchars(price_label($o['total'], $currency)); ?></td>
                  <td><span class="badge-status <?php echo $statusClass; ?>"><?php echo icon($statusIcon); ?> <?php echo ucfirst($status); ?></span></td>
                  <td><span class="muted"><?php echo date('M d, Y H:i', strtotime($o['order_date'])); ?></span></td>
                  <td>
                    <div class="product-actions">
                      <a class="btn btn-mini btn-primary" href="generate-invoice.php?order_id=<?php echo (int)$o['id']; ?>"><?php echo icon('file-invoice'); ?> Invoice</a>
                      <?php if ($role === 'admin'): ?>
                        <details style="display:inline-block">
                          <summary class="btn btn-mini btn-ghost"><?php echo icon('pen-to-square'); ?> Status</summary>
                          <div style="margin-top:6px; padding:10px; background:var(--card2); border-radius:14px; border:1px solid var(--border)">
                            <form method="post">
                              <input type="hidden" name="action" value="update_status">
                              <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                              <select class="select" name="status" style="margin-bottom:8px">
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="dispatched" <?php echo $status === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                              </select>
                              <button class="btn btn-primary btn-mini" type="submit"><?php echo icon('floppy-disk'); ?> Update</button>
                            </form>
                          </div>
                        </details>
                      <?php endif; ?>
                      <?php if ($role === 'customer'): ?>
                        <?php if ($o['status'] === 'pending'): ?>
                          <a class="btn btn-mini btn-primary" href="payment.php?order_id=<?php echo (int)$o['id']; ?>"><?php echo icon('credit-card'); ?> Pay Now</a>
                        <?php endif; ?>
                        <a class="btn btn-mini btn-ghost" href="track-order.php?order_id=<?php echo (int)$o['id']; ?>"><?php echo icon('location-dot'); ?> Track</a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
      <?php endif; ?>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>
