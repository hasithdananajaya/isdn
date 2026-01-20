<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

require_login();
$u = current_user();
$role = $u['role'];

$stats = [
  'products' => 0,
  'users' => 0,
  'orders' => 0,
  'revenue' => 0.0,
  'my_orders' => 0,
  'my_deliveries' => 0,
];

if ($role === 'admin') {
  $r = mysqli_query($conn, "SELECT COUNT(*) c FROM products"); if ($r) $stats['products'] = (int)mysqli_fetch_assoc($r)['c'];
  $r = mysqli_query($conn, "SELECT COUNT(*) c FROM users"); if ($r) $stats['users'] = (int)mysqli_fetch_assoc($r)['c'];
  $r = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total),0) s FROM orders"); if ($r){ $row=mysqli_fetch_assoc($r); $stats['orders']=(int)$row['c']; $stats['revenue']=(float)$row['s']; }
} elseif ($role === 'rdc') {
  $id = (int)$u['id'];
  $r = mysqli_query($conn, "SELECT COUNT(*) c FROM deliveries WHERE rdc_staff_id = $id"); if ($r) $stats['my_deliveries'] = (int)mysqli_fetch_assoc($r)['c'];
  $r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders"); if ($r) $stats['orders'] = (int)mysqli_fetch_assoc($r)['c'];
} else { // customer
  $id = (int)$u['id'];
  $r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE customer_id = $id"); if ($r) $stats['my_orders'] = (int)mysqli_fetch_assoc($r)['c'];
}

$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard | ISDN</title>
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
        <h2><?php echo icon('gauge'); ?> Dashboard</h2>
        <span class="pill"><?php echo icon('user'); ?> <?php echo htmlspecialchars($u['name']); ?> • <strong><?php echo strtoupper($role); ?></strong></span>
      </div>

      <section class="grid cols-3">
        <?php if ($role === 'admin'): ?>
          <div class="card pad lift" style="animation-delay:.05s">
            <div class="muted"><?php echo icon('boxes-stacked'); ?> Products</div>
            <div style="font-size:34px; font-weight:1000"><?php echo (int)$stats['products']; ?></div>
          </div>
          <div class="card pad lift" style="animation-delay:.12s">
            <div class="muted"><?php echo icon('users'); ?> Users</div>
            <div style="font-size:34px; font-weight:1000"><?php echo (int)$stats['users']; ?></div>
          </div>
          <div class="card pad lift" style="animation-delay:.19s">
            <div class="muted"><?php echo icon('chart-line'); ?> Total revenue</div>
            <div class="price" data-price="<?php echo htmlspecialchars((string)$stats['revenue']); ?>"><?php echo htmlspecialchars(price_label($stats['revenue'], $currency)); ?></div>
            <div class="muted"><?php echo (int)$stats['orders']; ?> orders</div>
          </div>
        <?php elseif ($role === 'rdc'): ?>
          <div class="card pad lift" style="animation-delay:.05s">
            <div class="muted"><?php echo icon('truck-fast'); ?> My deliveries</div>
            <div style="font-size:34px; font-weight:1000"><?php echo (int)$stats['my_deliveries']; ?></div>
          </div>
          <div class="card pad lift" style="animation-delay:.12s">
            <div class="muted"><?php echo icon('warehouse'); ?> RDC Location</div>
            <div style="font-size:18px; font-weight:1000"><?php echo htmlspecialchars($u['rdc_location'] ?: 'Not set'); ?></div>
          </div>
          <div class="card pad lift" style="animation-delay:.19s">
            <div class="muted"><?php echo icon('clipboard-list'); ?> Total orders (system)</div>
            <div style="font-size:34px; font-weight:1000"><?php echo (int)$stats['orders']; ?></div>
          </div>
        <?php else: ?>
          <div class="card pad lift" style="animation-delay:.05s">
            <div class="muted"><?php echo icon('clipboard-list'); ?> My orders</div>
            <div style="font-size:34px; font-weight:1000"><?php echo (int)$stats['my_orders']; ?></div>
          </div>
          <div class="card pad lift" style="animation-delay:.12s">
            <div class="muted"><?php echo icon('cart-shopping'); ?> Cart items</div>
            <div style="font-size:34px; font-weight:1000"><?php echo (int)cart_count(); ?></div>
          </div>
          <div class="card pad lift" style="animation-delay:.19s">
            <div class="muted"><?php echo icon('location-dot'); ?> Tracking</div>
            <div style="font-size:18px; font-weight:1000">Live map simulation</div>
            <div class="muted">Leaflet</div>
          </div>
        <?php endif; ?>
      </section>

      <section class="grid cols-2" style="margin-top:16px">
        <div class="card pad lift" style="animation-delay:.26s">
          <div class="section-head">
            <h2><?php echo icon('bolt'); ?> Quick actions</h2>
            <span class="muted">Fast navigation</span>
          </div>
          <div class="product-actions">
            <?php if ($role === 'admin'): ?>
              <a class="btn btn-primary" href="products.php"><?php echo icon('plus'); ?> Manage Products</a>
              <a class="btn btn-ghost" href="orders.php"><?php echo icon('clipboard-list'); ?> Manage Orders</a>
              <a class="btn btn-ghost" href="users.php"><?php echo icon('users'); ?> Manage Users</a>
              <a class="btn btn-ghost" href="delivery.php"><?php echo icon('truck-fast'); ?> Deliveries</a>
            <?php elseif ($role === 'rdc'): ?>
              <a class="btn btn-primary" href="delivery.php"><?php echo icon('truck-fast'); ?> My Deliveries</a>
              <a class="btn btn-ghost" href="products.php"><?php echo icon('warehouse'); ?> View Stock</a>
              <a class="btn btn-ghost" href="orders.php"><?php echo icon('clipboard-list'); ?> Orders</a>
            <?php else: ?>
              <a class="btn btn-primary" href="products.php"><?php echo icon('bag-shopping'); ?> Shop Products</a>
              <a class="btn btn-ghost" href="cart.php"><?php echo icon('cart-shopping'); ?> Go to Cart</a>
              <a class="btn btn-ghost" href="orders.php"><?php echo icon('clipboard-list'); ?> My Orders</a>
              <a class="btn btn-ghost" href="track-order.php"><?php echo icon('location-dot'); ?> Track Orders</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="card pad lift" style="animation-delay:.33s">
          <div class="section-head">
            <h2><?php echo icon('circle-info'); ?> System notes</h2>
            <span class="muted">Academic-ready</span>
          </div>
          <div class="timeline">
            <div class="timeline-item done">
              <div class="timeline-dot"><?php echo icon('lock'); ?></div>
              <div>
                <div style="font-weight:1000">Authentication + roles</div>
                <div class="muted">Sessions, MD5 passwords, role-based navigation and access checks.</div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"><?php echo icon('dollar-sign'); ?></div>
              <div>
                <div style="font-weight:1000">Currency + theme</div>
                <div class="muted">USD ↔ LKR conversion (1 USD = 320 LKR) + persistent dark mode.</div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>

