<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
?>
<?php $currency = currency_get(); ?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ISDN | Luxury Distribution Management</title>
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
      <section class="hero lift" style="animation-delay:.05s">
        <div class="hero-media" aria-hidden="true"></div>
        <div class="hero-content">
          <div class="hero-kicker">
            <?php echo icon('crown'); ?>
            <strong>Premium ISDN Platform</strong>
            <span class="muted" style="color:rgba(255,255,255,.82)">Orders • Distribution • Deliveries • Analytics</span>
          </div>
          <h1>Luxury-grade distribution management built for modern Island commerce.</h1>
          <p>
            Manage products, place orders, assign regional deliveries, track status, and analyze performance —
            all inside a smooth, world-class UI.
          </p>
          <div class="hero-actions">
            <?php if (!is_logged_in()): ?>
              <a class="btn btn-primary" href="login.php"><?php echo icon('right-to-bracket'); ?> Login</a>
              <a class="btn btn-primary" href="signup.php"><?php echo icon('user-plus'); ?> Create Customer Account</a>
            <?php else: ?>
              <a class="btn btn-primary" href="dashboard.php"><?php echo icon('gauge'); ?> Go to Dashboard</a>
              <a class="btn btn-primary" href="products.php"><?php echo icon('bag-shopping'); ?> Browse Products</a>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="grid cols-3" style="margin-top:16px">
        <div class="card pad lift" style="animation-delay:.12s">
          <div class="section-head">
            <h2><?php echo icon('shield-halved'); ?> Secure Access</h2>
            <span class="muted">Role-based</span>
          </div>
          <p class="muted">Admin, RDC staff, and customers see exactly what they need — nothing more, nothing less.</p>
        </div>
        <div class="card pad lift" style="animation-delay:.20s">
          <div class="section-head">
            <h2><?php echo icon('truck-fast'); ?> Delivery Flow</h2>
            <span class="muted">RDC-ready</span>
          </div>
          <p class="muted">Assign deliveries, update status, and track progress with a premium timeline experience.</p>
        </div>
        <div class="card pad lift" style="animation-delay:.28s">
          <div class="section-head">
            <h2><?php echo icon('chart-line'); ?> Analytics</h2>
            <span class="muted">Chart</span>
          </div>
          <p class="muted">See revenue, orders, and performance insights with beautiful, responsive charts.</p>
        </div>
      </section>
    </div>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
  <script>
    document.addEventListener('pointermove', (e) => {
      const t = e.target.closest('.btn');
      if (!t) return;
      const r = t.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      t.style.setProperty('--x', x + '%');
      t.style.setProperty('--y', y + '%');
    });
  </script>
</body>
</html>

