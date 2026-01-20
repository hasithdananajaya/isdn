<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

require_role('admin');

$stats = [
  'total_revenue' => 0.0,
  'monthly_revenue' => 0.0,
  'total_orders' => 0,
  'pending_orders' => 0,
  'dispatched_orders' => 0,
  'delivered_orders' => 0,
  'active_customers' => 0,
  'total_products' => 0,
  'low_stock_products' => 0,
];

$r = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) s FROM orders");
if ($r) $stats['total_revenue'] = (float)mysqli_fetch_assoc($r)['s'];

$r = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) s FROM orders WHERE MONTH(order_date) = MONTH(CURRENT_DATE) AND YEAR(order_date) = YEAR(CURRENT_DATE)");
if ($r) $stats['monthly_revenue'] = (float)mysqli_fetch_assoc($r)['s'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders");
if ($r) $stats['total_orders'] = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE status = 'pending'");
if ($r) $stats['pending_orders'] = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE status = 'dispatched'");
if ($r) $stats['dispatched_orders'] = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE status = 'delivered'");
if ($r) $stats['delivered_orders'] = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(DISTINCT customer_id) c FROM orders");
if ($r) $stats['active_customers'] = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM products");
if ($r) $stats['total_products'] = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM products WHERE stock <= 10");
if ($r) $stats['low_stock_products'] = (int)mysqli_fetch_assoc($r)['c'];

$topProducts = [];
$r = mysqli_query($conn, "SELECT p.name, p.category, SUM(oi.quantity) total_qty, SUM(oi.quantity * oi.price) total_revenue
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           GROUP BY p.id, p.name, p.category
                           ORDER BY total_qty DESC
                           LIMIT 5");
if ($r) while ($row = mysqli_fetch_assoc($r)) $topProducts[] = $row;

$recentOrders = [];
$r = mysqli_query($conn, "SELECT o.*, u.name customer_name
                          FROM orders o
                          LEFT JOIN users u ON o.customer_id = u.id
                          ORDER BY o.order_date DESC
                          LIMIT 5");
if ($r) while ($row = mysqli_fetch_assoc($r)) $recentOrders[] = $row;

$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics | ISDN</title>
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
        <h2><?php echo icon('chart-line'); ?> Analytics Dashboard</h2>
        <div class="product-actions">
          <a class="btn btn-primary" href="advanced-analytics.php"><?php echo icon('chart-pie'); ?> Advanced Charts</a>
          <details style="display:inline-block">
            <summary class="btn btn-ghost"><?php echo icon('file-export'); ?> Export Data</summary>
            <div style="margin-top:6px; padding:10px; background:var(--card2); border-radius:14px; border:1px solid var(--border); position:absolute; z-index:10">
              <a class="btn btn-mini btn-primary" href="export-excel.php?type=orders" style="display:block; margin-bottom:6px; text-align:center"><?php echo icon('clipboard-list'); ?> Export Orders (<?php echo htmlspecialchars($currency); ?>)</a>
              <a class="btn btn-mini btn-primary" href="export-excel.php?type=products" style="display:block; margin-bottom:6px; text-align:center"><?php echo icon('boxes-stacked'); ?> Export Products (<?php echo htmlspecialchars($currency); ?>)</a>
              <a class="btn btn-mini btn-primary" href="export-excel.php?type=users" style="display:block; text-align:center"><?php echo icon('users'); ?> Export Users</a>
            </div>
          </details>
        </div>
      </div>

      <section class="grid cols-3" style="margin-bottom:16px">
        <div class="card pad lift" style="animation-delay:.05s">
          <div class="muted"><?php echo icon('dollar-sign'); ?> Total revenue</div>
          <div class="price" data-price="<?php echo htmlspecialchars((string)$stats['total_revenue']); ?>" style="font-size:28px; margin-top:6px"><?php echo htmlspecialchars(price_label($stats['total_revenue'], $currency)); ?></div>
        </div>
        <div class="card pad lift" style="animation-delay:.12s">
          <div class="muted"><?php echo icon('calendar'); ?> Monthly revenue</div>
          <div class="price" data-price="<?php echo htmlspecialchars((string)$stats['monthly_revenue']); ?>" style="font-size:28px; margin-top:6px"><?php echo htmlspecialchars(price_label($stats['monthly_revenue'], $currency)); ?></div>
        </div>
        <div class="card pad lift" style="animation-delay:.19s">
          <div class="muted"><?php echo icon('clipboard-list'); ?> Total orders</div>
          <div style="font-size:34px; font-weight:1000; margin-top:6px"><?php echo (int)$stats['total_orders']; ?></div>
        </div>
      </section>

      <section class="grid cols-3" style="margin-bottom:16px">
        <div class="card pad lift" style="animation-delay:.26s">
          <div class="muted"><?php echo icon('clock'); ?> Pending</div>
          <div style="font-size:34px; font-weight:1000; margin-top:6px"><?php echo (int)$stats['pending_orders']; ?></div>
        </div>
        <div class="card pad lift" style="animation-delay:.33s">
          <div class="muted"><?php echo icon('truck-fast'); ?> Dispatched</div>
          <div style="font-size:34px; font-weight:1000; margin-top:6px"><?php echo (int)$stats['dispatched_orders']; ?></div>
        </div>
        <div class="card pad lift" style="animation-delay:.40s">
          <div class="muted"><?php echo icon('circle-check'); ?> Delivered</div>
          <div style="font-size:34px; font-weight:1000; margin-top:6px"><?php echo (int)$stats['delivered_orders']; ?></div>
        </div>
      </section>

      <section class="grid cols-3" style="margin-bottom:16px">
        <div class="card pad lift" style="animation-delay:.47s">
          <div class="muted"><?php echo icon('users'); ?> Active customers</div>
          <div style="font-size:34px; font-weight:1000; margin-top:6px"><?php echo (int)$stats['active_customers']; ?></div>
        </div>
        <div class="card pad lift" style="animation-delay:.54s">
          <div class="muted"><?php echo icon('boxes-stacked'); ?> Total products</div>
          <div style="font-size:34px; font-weight:1000; margin-top:6px"><?php echo (int)$stats['total_products']; ?></div>
        </div>
        <div class="card pad lift" style="animation-delay:.61s">
          <div class="muted"><?php echo icon('triangle-exclamation'); ?> Low stock</div>
          <div style="font-size:34px; font-weight:1000; margin-top:6px"><?php echo (int)$stats['low_stock_products']; ?></div>
        </div>
      </section>

      <div class="grid cols-2" style="margin-top:16px">
        <section class="card pad lift" style="animation-delay:.68s">
          <div class="section-head">
            <h2><?php echo icon('trophy'); ?> Top selling products</h2>
            <span class="muted">By quantity</span>
          </div>
          <?php if ($topProducts): ?>
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Qty sold</th>
                  <th>Revenue</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($topProducts as $p): ?>
                  <tr>
                    <td style="font-weight:1000"><?php echo htmlspecialchars($p['name']); ?></td>
                    <td class="muted"><?php echo htmlspecialchars($p['category']); ?></td>
                    <td><span class="badge-status"><?php echo (int)$p['total_qty']; ?></span></td>
                    <td class="price" data-price="<?php echo htmlspecialchars((string)$p['total_revenue']); ?>"><?php echo htmlspecialchars(price_label($p['total_revenue'], $currency)); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="muted">No sales data yet.</div>
          <?php endif; ?>
        </section>

        <section class="card pad lift" style="animation-delay:.75s">
          <div class="section-head">
            <h2><?php echo icon('clock-rotate-left'); ?> Recent orders</h2>
            <span class="muted">Latest 5</span>
          </div>
          <?php if ($recentOrders): ?>
            <div class="timeline">
              <?php foreach ($recentOrders as $o): ?>
                <div class="timeline-item">
                  <div class="timeline-dot"><?php echo icon('receipt'); ?></div>
                  <div>
                    <div style="font-weight:1000">Order #<?php echo (int)$o['id']; ?></div>
                    <div class="muted"><?php echo htmlspecialchars($o['customer_name'] ?: 'N/A'); ?></div>
                    <div class="muted" style="font-size:12px"><?php echo date('M d, Y H:i', strtotime($o['order_date'])); ?> • <span class="price" data-price="<?php echo htmlspecialchars((string)$o['total']); ?>"><?php echo htmlspecialchars(price_label($o['total'], $currency)); ?></span></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="muted">No recent orders.</div>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>
