<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

$u = current_user();
$role = $u ? $u['role'] : 'guest';
$cartCount = ($role === 'customer') ? cart_count() : 0;
$currency = currency_get();

$currentPage = basename($_SERVER['PHP_SELF']);
function is_active($page) {
  global $currentPage;
  return $currentPage === $page ? 'active' : '';
}
?>

<nav class="navbar">
  <div class="nav-inner">
    <a class="brand" href="index.php">
      <span class="brand-mark">
        <i class="fa-solid fa-gem"></i>
      </span>
      <span class="brand-text">
        <span class="brand-title">IslandLink</span>
        <span class="brand-sub">Sales Distribution Network</span>
      </span>
    </a>

    <div class="nav-spacer"></div>

    <?php include __DIR__ . '/navbar-controls.php'; ?>

    <div class="nav-links">
      <?php if ($role === 'admin'): ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>"><?php echo icon('house'); ?> Home</a>
        <a href="dashboard.php" class="<?php echo is_active('dashboard.php'); ?>"><?php echo icon('gauge'); ?> Dashboard</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>"><?php echo icon('boxes-stacked'); ?> Products</a>
        <a href="orders.php" class="<?php echo is_active('orders.php'); ?>"><?php echo icon('clipboard-list'); ?> Orders</a>
        <a href="delivery.php" class="<?php echo is_active('delivery.php'); ?>"><?php echo icon('truck-fast'); ?> Deliveries</a>
        <a href="users.php" class="<?php echo is_active('users.php'); ?>"><?php echo icon('users'); ?> Users</a>
        <a href="analytics.php" class="<?php echo (is_active('analytics.php') || is_active('advanced-analytics.php')); ?>"><?php echo icon('chart-line'); ?> Analytics</a>
        <a href="advanced-analytics.php" class="<?php echo is_active('advanced-analytics.php'); ?>"><?php echo icon('chart-pie'); ?> Advanced</a>
        <a href="profile.php" class="<?php echo is_active('profile.php'); ?>"><?php echo icon('user'); ?> Profile</a>
        <a class="btn btn-mini" href="logout.php"><?php echo icon('right-from-bracket'); ?> Logout</a>
      <?php elseif ($role === 'rdc'): ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>"><?php echo icon('house'); ?> Home</a>
        <a href="dashboard.php" class="<?php echo is_active('dashboard.php'); ?>"><?php echo icon('gauge'); ?> Dashboard</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>"><?php echo icon('warehouse'); ?> Stock</a>
        <a href="delivery.php" class="<?php echo is_active('delivery.php'); ?>"><?php echo icon('truck-fast'); ?> My Deliveries</a>
        <a href="orders.php" class="<?php echo is_active('orders.php'); ?>"><?php echo icon('clipboard-list'); ?> Orders</a>
        <a href="profile.php" class="<?php echo is_active('profile.php'); ?>"><?php echo icon('user'); ?> Profile</a>
        <a class="btn btn-mini" href="logout.php"><?php echo icon('right-from-bracket'); ?> Logout</a>
      <?php elseif ($role === 'customer'): ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>"><?php echo icon('house'); ?> Home</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>"><?php echo icon('bag-shopping'); ?> Shop</a>
        <a class="nav-cart <?php echo is_active('cart.php'); ?>" href="cart.php" aria-label="Cart">
          <?php echo icon('cart-shopping'); ?>
          <span class="badge <?php echo $cartCount ? 'badge-pulse' : ''; ?>"><?php echo (int)$cartCount; ?></span>
        </a>
        <a href="orders.php" class="<?php echo is_active('orders.php'); ?>"><?php echo icon('clipboard-list'); ?> My Orders</a>
        <a href="track-order.php" class="<?php echo is_active('track-order.php'); ?>"><?php echo icon('location-dot'); ?> Track Orders</a>
        <a href="profile.php" class="<?php echo is_active('profile.php'); ?>"><?php echo icon('user'); ?> Profile</a>
        <a class="btn btn-mini" href="logout.php"><?php echo icon('right-from-bracket'); ?> Logout</a>
      <?php else: ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>"><?php echo icon('house'); ?> Home</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>"><?php echo icon('bag-shopping'); ?> Products</a>
        <a href="login.php" class="<?php echo is_active('login.php'); ?>"><?php echo icon('right-to-bracket'); ?> Login</a>
        <a class="btn btn-mini" href="signup.php"><?php echo icon('user-plus'); ?> Sign Up</a>
      <?php endif; ?>
    </div>

    <button class="nav-burger" type="button" aria-label="Menu" data-nav-burger>
      <i class="fa-solid fa-bars"></i>
    </button>
  </div>

  <div class="nav-drawer" data-nav-drawer>
    <div class="nav-drawer-inner">
      <?php if ($role === 'admin'): ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>">Home</a>
        <a href="dashboard.php" class="<?php echo is_active('dashboard.php'); ?>">Dashboard</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>">Products</a>
        <a href="orders.php" class="<?php echo is_active('orders.php'); ?>">Orders</a>
        <a href="delivery.php" class="<?php echo is_active('delivery.php'); ?>">Deliveries</a>
        <a href="users.php" class="<?php echo is_active('users.php'); ?>">Users</a>
        <a href="analytics.php" class="<?php echo (is_active('analytics.php') || is_active('advanced-analytics.php')); ?>">Analytics</a>
        <a href="advanced-analytics.php" class="<?php echo is_active('advanced-analytics.php'); ?>">Advanced</a>
        <a href="profile.php" class="<?php echo is_active('profile.php'); ?>">Profile</a>
        <a href="logout.php">Logout</a>
      <?php elseif ($role === 'rdc'): ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>">Home</a>
        <a href="dashboard.php" class="<?php echo is_active('dashboard.php'); ?>">Dashboard</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>">Stock</a>
        <a href="delivery.php" class="<?php echo is_active('delivery.php'); ?>">My Deliveries</a>
        <a href="orders.php" class="<?php echo is_active('orders.php'); ?>">Orders</a>
        <a href="profile.php" class="<?php echo is_active('profile.php'); ?>">Profile</a>
        <a href="logout.php">Logout</a>
      <?php elseif ($role === 'customer'): ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>">Home</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>">Shop</a>
        <a href="cart.php" class="<?php echo is_active('cart.php'); ?>">Cart</a>
        <a href="orders.php" class="<?php echo is_active('orders.php'); ?>">My Orders</a>
        <a href="track-order.php" class="<?php echo is_active('track-order.php'); ?>">Track Orders</a>
        <a href="profile.php" class="<?php echo is_active('profile.php'); ?>">Profile</a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="index.php" class="<?php echo is_active('index.php'); ?>">Home</a>
        <a href="products.php" class="<?php echo is_active('products.php'); ?>">Products</a>
        <a href="login.php" class="<?php echo is_active('login.php'); ?>">Login</a>
        <a href="signup.php" class="<?php echo is_active('signup.php'); ?>">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
  (function(){
    const burger = document.querySelector('[data-nav-burger]');
    const drawer = document.querySelector('[data-nav-drawer]');
    if (!burger || !drawer) return;
    burger.addEventListener('click', () => drawer.classList.toggle('open'));
    drawer.addEventListener('click', (e) => {
      if (e.target === drawer) drawer.classList.remove('open');
    });
  })();
</script>

