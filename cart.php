<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

require_any_role(['customer']);

$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $cart = cart_get();

  if ($action === 'set_qty') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($pid > 0) {
      if ($qty <= 0) unset($cart[$pid]);
      else $cart[$pid] = max(1, min(99, $qty));
      $_SESSION['cart'] = $cart;
      flash_set('msg', 'Cart updated.');
    }
    header('Location: cart.php');
    exit;
  }

  if ($action === 'clear') {
    $_SESSION['cart'] = [];
    flash_set('msg', 'Cart cleared.');
    header('Location: cart.php');
    exit;
  }
}

$cart = cart_get();
$items = [];
$subtotal = 0.0;

if ($cart) {
  $ids = array_map('intval', array_keys($cart));
  $idList = implode(',', $ids);
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
}

$tax = $subtotal * 0.05;
$shipping = $items ? 3.00 : 0.00;
$total = $subtotal + $tax + $shipping;
$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cart | ISDN</title>
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
        <h2><?php echo icon('cart-shopping'); ?> Shopping Cart</h2>
        <span class="muted">Real-time totals • 5% tax • shipping</span>
      </div>

      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

      <?php if (!$items): ?>
        <section class="card pad lift" style="animation-delay:.06s">
          <strong>Your cart is empty.</strong>
          <div class="muted" style="margin-top:6px">Browse luxury products and add items to your cart.</div>
          <div class="hero-actions" style="margin-top:14px">
            <a class="btn btn-primary" href="products.php"><?php echo icon('bag-shopping'); ?> Continue shopping</a>
          </div>
        </section>
      <?php else: ?>
        <div class="grid cols-2" style="align-items:start">
          <section class="card pad lift" style="animation-delay:.06s">
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Line</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $p): ?>
                  <tr>
                    <td>
                      <div style="font-weight:1000"><?php echo htmlspecialchars($p['name']); ?></div>
                      <div class="muted" style="font-size:12px"><?php echo htmlspecialchars($p['category']); ?> • <?php echo htmlspecialchars($p['rdc_location']); ?></div>
                    </td>
                    <td class="price" data-price="<?php echo htmlspecialchars((string)$p['price']); ?>"><?php echo htmlspecialchars(price_label($p['price'], $currency)); ?></td>
                    <td>
                      <form method="post" class="qty" style="justify-content:flex-start">
                        <input type="hidden" name="action" value="set_qty">
                        <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                        <input class="input" name="qty" type="number" min="0" max="99" value="<?php echo (int)$p['_qty']; ?>">
                        <button type="submit" title="Update"><?php echo icon('arrows-rotate'); ?></button>
                      </form>
                    </td>
                    <td class="price" data-price="<?php echo htmlspecialchars((string)$p['_line']); ?>"><?php echo htmlspecialchars(price_label($p['_line'], $currency)); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <form method="post" onsubmit="return confirm('Clear cart?');">
              <input type="hidden" name="action" value="clear">
              <button class="btn btn-ghost" type="submit"><?php echo icon('trash'); ?> Clear cart</button>
            </form>
          </section>

          <aside class="card pad lift" style="animation-delay:.12s">
            <div class="section-head">
              <h2><?php echo icon('receipt'); ?> Summary</h2>
              <span class="muted">USD base</span>
            </div>
            <div class="cart-summary">
              <div class="summary-row">
                <span class="muted">Subtotal</span>
                <strong class="price" data-price="<?php echo htmlspecialchars((string)$subtotal); ?>"><?php echo htmlspecialchars(price_label($subtotal, $currency)); ?></strong>
              </div>
              <div class="summary-row">
                <span class="muted">Tax (5%)</span>
                <strong class="price" data-price="<?php echo htmlspecialchars((string)$tax); ?>"><?php echo htmlspecialchars(price_label($tax, $currency)); ?></strong>
              </div>
              <div class="summary-row">
                <span class="muted">Shipping</span>
                <strong class="price" data-price="<?php echo htmlspecialchars((string)$shipping); ?>"><?php echo htmlspecialchars(price_label($shipping, $currency)); ?></strong>
              </div>
              <div class="summary-row" style="padding-top:10px; border-top:1px solid var(--border)">
                <span style="font-weight:1000">Total</span>
                <strong class="price" data-price="<?php echo htmlspecialchars((string)$total); ?>"><?php echo htmlspecialchars(price_label($total, $currency)); ?></strong>
              </div>
              <a class="btn btn-primary" href="checkout.php"><?php echo icon('credit-card'); ?> Proceed to checkout</a>
              <a class="btn btn-ghost" href="products.php"><?php echo icon('bag-shopping'); ?> Continue shopping</a>
            </div>
          </aside>
        </div>
      <?php endif; ?>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>

