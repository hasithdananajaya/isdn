<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

$u = current_user();
$role = $u ? $u['role'] : 'guest';

$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

function ensure_dir($path) {
  if (!is_dir($path)) @mkdir($path, 0777, true);
}

if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add' || $action === 'edit') {
    $name = esc($conn, $_POST['name'] ?? '');
    $category = esc($conn, $_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $rdc_location = esc($conn, $_POST['rdc_location'] ?? '');

    if ($name === '' || $category === '' || $rdc_location === '') {
      flash_set('err', 'Please fill all required fields (name, category, RDC location).');
      header('Location: products.php');
      exit;
    }

    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
      $tmp = $_FILES['image']['tmp_name'];
      $size = (int)($_FILES['image']['size'] ?? 0);
      $originalName = $_FILES['image']['name'] ?? '';
      $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','webp'];
      
      if ($size === 0) {
        flash_set('err', 'Uploaded file is empty.');
        header('Location: products.php');
        exit;
      }
      if ($size > 3 * 1024 * 1024) {
        flash_set('err', 'Image too large (max 3MB).');
        header('Location: products.php');
        exit;
      }
      if (empty($ext) || !in_array($ext, $allowed, true)) {
        flash_set('err', 'Invalid image type. Use JPG/PNG/WebP.');
        header('Location: products.php');
        exit;
      }
      
      ensure_dir(__DIR__ . '/uploads/products');
      $fname = 'p_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
      $dest = __DIR__ . '/uploads/products/' . $fname;
      
      if (move_uploaded_file($tmp, $dest)) {
        $imagePath = 'uploads/products/' . $fname;
      } else {
        flash_set('err', 'Failed to save uploaded image. Please try again.');
        header('Location: products.php');
        exit;
      }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
      $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
      ];
      $errorMsg = $uploadErrors[$_FILES['image']['error']] ?? 'Unknown upload error.';
      flash_set('err', 'Image upload error: ' . $errorMsg);
      header('Location: products.php');
      exit;
    }

    if ($action === 'add') {
      $imgSql = $imagePath ? ("'" . esc($conn, $imagePath) . "'") : "NULL";
      $sql = "INSERT INTO products (name, category, price, stock, rdc_location, image)
              VALUES ('$name', '$category', " . number_format($price, 2, '.', '') . ", $stock, '$rdc_location', $imgSql)";
      if (mysqli_query($conn, $sql)) {
        flash_set('msg', 'Product added successfully.');
      } else {
        $errorMsg = mysqli_error($conn);
        flash_set('err', 'Failed to add product. ' . ($errorMsg ? htmlspecialchars($errorMsg) : 'Database error.'));
      }
      header('Location: products.php');
      exit;
    } else {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) {
        flash_set('err', 'Invalid product selected.');
        header('Location: products.php');
        exit;
      }
      $updates = [
        "name='" . esc($conn, $name) . "'",
        "category='" . esc($conn, $category) . "'",
        "price=" . number_format($price, 2, '.', ''),
        "stock=$stock",
        "rdc_location='" . esc($conn, $rdc_location) . "'"
      ];
      
      if ($imagePath) {
        $oldImgQuery = mysqli_query($conn, "SELECT image FROM products WHERE id=$id LIMIT 1");
        if ($oldImgQuery && $oldRow = mysqli_fetch_assoc($oldImgQuery)) {
          if (!empty($oldRow['image']) && file_exists(__DIR__ . '/' . $oldRow['image'])) {
            @unlink(__DIR__ . '/' . $oldRow['image']);
          }
        }
        $updates[] = "image='" . esc($conn, $imagePath) . "'";
      }
      
      $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id=$id LIMIT 1";
      
      try {
        $result = mysqli_query($conn, $sql);
        if ($result) {
          $affected = mysqli_affected_rows($conn);
          if ($affected >= 0) {
            flash_set('msg', 'Product updated successfully.');
          } else {
            flash_set('err', 'Product update completed but no rows were affected.');
          }
        } else {
          throw new Exception(mysqli_error($conn));
        }
      } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        flash_set('err', 'Failed to update product: ' . htmlspecialchars($errorMsg));
        error_log("Product update error: SQL=$sql, Error=$errorMsg");
      }
      header('Location: products.php');
      exit;
    }
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      flash_set('err', 'Invalid product selected.');
      header('Location: products.php');
      exit;
    }
    
    $orderCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM order_items WHERE product_id=$id");
    $hasOrders = false;
    if ($orderCheck) {
      $row = mysqli_fetch_assoc($orderCheck);
      $hasOrders = (int)$row['count'] > 0;
    }
    
    if ($hasOrders) {
      flash_set('err', 'Cannot delete product: This product has existing orders. Products with order history cannot be deleted to maintain data integrity.');
      header('Location: products.php');
      exit;
    }
    
    $imgQuery = mysqli_query($conn, "SELECT image FROM products WHERE id=$id LIMIT 1");
    $oldImage = null;
    if ($imgQuery && $imgRow = mysqli_fetch_assoc($imgQuery)) {
      $oldImage = $imgRow['image'];
    }
    
    try {
      $deleteResult = mysqli_query($conn, "DELETE FROM products WHERE id=$id LIMIT 1");
      if ($deleteResult && mysqli_affected_rows($conn) > 0) {
        if ($oldImage && file_exists(__DIR__ . '/' . $oldImage)) {
          @unlink(__DIR__ . '/' . $oldImage);
        }
        flash_set('msg', 'Product deleted successfully.');
      } else {
        throw new Exception(mysqli_error($conn));
      }
    } catch (Exception $e) {
      $errorMsg = $e->getMessage();
      if (strpos($errorMsg, 'foreign key constraint') !== false) {
        flash_set('err', 'Cannot delete product: This product is referenced in existing orders. Products with order history cannot be deleted.');
      } else {
        flash_set('err', 'Failed to delete product: ' . htmlspecialchars($errorMsg));
      }
      error_log("Product delete error: Product ID=$id, Error=$errorMsg");
    }
    
    header('Location: products.php');
    exit;
  }
}

if ($role === 'customer' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
  $pid = (int)($_POST['product_id'] ?? 0);
  $qty = (int)($_POST['qty'] ?? 1);
  if ($pid > 0) {
    $qty = max(1, min(99, $qty));
    $cart = cart_get();
    $cart[$pid] = (int)($cart[$pid] ?? 0) + $qty;
    $_SESSION['cart'] = $cart;
    flash_set('msg', 'Added to cart.');
  }
  header('Location: products.php');
  exit;
}

$q = esc($conn, $_GET['q'] ?? '');
$cat = esc($conn, $_GET['category'] ?? '');
$rdc = esc($conn, $_GET['rdc'] ?? '');

$where = [];
if ($q !== '') $where[] = "(name LIKE '%$q%' OR category LIKE '%$q%' OR rdc_location LIKE '%$q%')";
if ($cat !== '') $where[] = "category = '$cat'";
if ($rdc !== '') $where[] = "rdc_location = '$rdc'";
if ($role === 'rdc' && !empty($u['rdc_location'])) {
  $where[] = "rdc_location = '" . esc($conn, $u['rdc_location']) . "'";
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$cats = [];
$r = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");
if ($r) while ($row = mysqli_fetch_assoc($r)) $cats[] = $row['category'];

$rdcs = [];
$r = mysqli_query($conn, "SELECT DISTINCT rdc_location FROM products ORDER BY rdc_location");
if ($r) while ($row = mysqli_fetch_assoc($r)) $rdcs[] = $row['rdc_location'];

$products = [];
$r = mysqli_query($conn, "SELECT * FROM products $whereSql ORDER BY created_at DESC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $products[] = $row;

$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Products | ISDN</title>
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
        <h2><?php echo $role === 'admin' ? icon('boxes-stacked') . ' Product Management' : icon('bag-shopping') . ' Product Catalog'; ?></h2>
        <span class="muted"><?php echo $role === 'rdc' ? 'RDC stock view' : ($role === 'admin' ? 'Admin CRUD' : 'Browse & add to cart'); ?></span>
      </div>

      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

      <form class="filters lift" method="get" style="animation-delay:.06s">
        <input class="input" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search products, categories, RDC…">
        <select class="select" name="category">
          <option value="">All categories</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $cat === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
          <?php endforeach; ?>
        </select>
        <select class="select" name="rdc" <?php echo $role === 'rdc' ? 'disabled' : ''; ?>>
          <option value="">All RDCs</option>
          <?php foreach ($rdcs as $rLoc): ?>
            <option value="<?php echo htmlspecialchars($rLoc); ?>" <?php echo $rdc === $rLoc ? 'selected' : ''; ?>><?php echo htmlspecialchars($rLoc); ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit"><?php echo icon('magnifying-glass'); ?> Filter</button>
      </form>

      <?php if ($role === 'admin'): ?>
        <section class="card pad lift" style="margin:16px 0; animation-delay:.10s">
          <div class="section-head">
            <h2><?php echo icon('plus'); ?> Add product</h2>
            <span class="muted">Image optional</span>
          </div>
          <form class="form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="grid cols-2">
              <div class="field">
                <label>Name</label>
                <input class="input" name="name" required>
              </div>
              <div class="field">
                <label>Category</label>
                <input class="input" name="category" placeholder="e.g. Grocery" required>
              </div>
            </div>
            <div class="grid cols-2">
              <div class="field">
                <label>Price (USD)</label>
                <input class="input" name="price" type="number" step="0.01" min="0" required>
              </div>
              <div class="field">
                <label>Stock</label>
                <input class="input" name="stock" type="number" min="0" required>
              </div>
            </div>
            <div class="grid cols-2">
              <div class="field">
                <label>RDC location</label>
                <input class="input" name="rdc_location" placeholder="e.g. Colombo RDC" required>
              </div>
              <div class="field">
                <label>Image</label>
                <input class="input" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
              </div>
            </div>
            <button class="btn btn-primary" type="submit"><?php echo icon('sparkles'); ?> Add product</button>
          </form>
        </section>
      <?php endif; ?>

      <section class="product-grid">
        <?php foreach ($products as $i => $p): ?>
          <?php
            $stock = (int)$p['stock'];
            $stockClass = $stock <= 0 ? 'stock-out' : ($stock <= 10 ? 'stock-low' : 'stock-ok');
            $stockLabel = $stock <= 0 ? 'Out of stock' : ($stock <= 10 ? 'Low stock' : 'In stock');
            $img = $p['image'] ? $p['image'] : 'https://images.unsplash.com/photo-1526367790999-0150786686a2?auto=format&fit=crop&w=1200&q=80';
          ?>
          <article class="product-card" style="animation-delay: <?php echo (0.04 * ($i % 10)) . 's'; ?>">
            <div class="product-media">
              <img loading="lazy" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
            </div>
            <div class="product-body">
              <p class="product-name"><?php echo htmlspecialchars($p['name']); ?></p>
              <div class="product-meta">
                <span><?php echo icon('tag'); ?> <?php echo htmlspecialchars($p['category']); ?></span>
                <span><?php echo icon('warehouse'); ?> <?php echo htmlspecialchars($p['rdc_location']); ?></span>
              </div>
              <div style="margin-top:10px; display:flex; align-items:center; justify-content:space-between; gap:10px">
                <div class="price" data-price="<?php echo htmlspecialchars((string)$p['price']); ?>"><?php echo htmlspecialchars(price_label($p['price'], $currency)); ?></div>
                <span class="stock-badge <?php echo $stockClass; ?>"><?php echo icon('boxes-stacked'); ?> <?php echo $stockLabel; ?> (<?php echo $stock; ?>)</span>
              </div>

              <div class="product-actions">
                <?php if ($role === 'customer'): ?>
                  <form method="post" style="display:flex; gap:10px; flex:1">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                    <input class="input" name="qty" type="number" min="1" max="99" value="1" style="width:90px">
                    <button class="btn btn-primary" type="submit" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                      <?php echo icon('cart-plus'); ?> Add
                    </button>
                  </form>
                <?php elseif ($role === 'admin'): ?>
                  <a class="btn btn-primary" href="product-edit.php?id=<?php echo (int)$p['id']; ?>" style="width:100%;">
                    <?php echo icon('pen-to-square'); ?> Edit Product
                  </a>
                <?php else: ?>
                  <a class="btn btn-primary" href="<?php echo is_logged_in() ? 'products.php' : 'login.php'; ?>">
                    <?php echo icon('right-to-bracket'); ?> Login to order
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <?php if (!$products): ?>
        <div class="card pad" style="margin-top:16px">
          <strong>No products found.</strong>
          <div class="muted">Try clearing filters or adding products (Admin).</div>
        </div>
      <?php endif; ?>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>

