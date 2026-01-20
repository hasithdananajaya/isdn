<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/price-helper.php';

require_role('admin');
$u = current_user();

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
  header('Location: products.php');
  exit;
}

$product = null;
$r = mysqli_query($conn, "SELECT * FROM products WHERE id = $productId LIMIT 1");
if ($r && mysqli_num_rows($r) === 1) {
  $product = mysqli_fetch_assoc($r);
} else {
  flash_set('err', 'Product not found.');
  header('Location: products.php');
  exit;
}

$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

function ensure_dir($path) {
  if (!is_dir($path)) @mkdir($path, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'update') {
    $name = esc($conn, $_POST['name'] ?? '');
    $category = esc($conn, $_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $rdc_location = esc($conn, $_POST['rdc_location'] ?? '');

    if ($name === '' || $category === '' || $rdc_location === '') {
      flash_set('err', 'Please fill all required fields (name, category, RDC location).');
      header('Location: product-edit.php?id=' . $productId);
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
        header('Location: product-edit.php?id=' . $productId);
        exit;
      }
      if ($size > 3 * 1024 * 1024) {
        flash_set('err', 'Image too large (max 3MB).');
        header('Location: product-edit.php?id=' . $productId);
        exit;
      }
      if (empty($ext) || !in_array($ext, $allowed, true)) {
        flash_set('err', 'Invalid image type. Use JPG/PNG/WebP.');
        header('Location: product-edit.php?id=' . $productId);
        exit;
      }
      
      ensure_dir(__DIR__ . '/uploads/products');
      $fname = 'p_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
      $dest = __DIR__ . '/uploads/products/' . $fname;
      
      if (move_uploaded_file($tmp, $dest)) {
        $imagePath = 'uploads/products/' . $fname;
        if (!empty($product['image']) && file_exists(__DIR__ . '/' . $product['image'])) {
          @unlink(__DIR__ . '/' . $product['image']);
        }
      } else {
        flash_set('err', 'Failed to save uploaded image. Please try again.');
        header('Location: product-edit.php?id=' . $productId);
        exit;
      }
    }

    $updates = [
      "name='" . esc($conn, $name) . "'",
      "category='" . esc($conn, $category) . "'",
      "price=" . number_format($price, 2, '.', ''),
      "stock=$stock",
      "rdc_location='" . esc($conn, $rdc_location) . "'"
    ];
    
    if ($imagePath) {
      $updates[] = "image='" . esc($conn, $imagePath) . "'";
    }
    
    $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id=$productId LIMIT 1";
    
    try {
      $result = mysqli_query($conn, $sql);
      if ($result) {
        flash_set('msg', 'Product updated successfully.');
        header('Location: product-edit.php?id=' . $productId);
        exit;
      } else {
        throw new Exception(mysqli_error($conn));
      }
    } catch (Exception $e) {
      $errorMsg = $e->getMessage();
      flash_set('err', 'Failed to update product: ' . htmlspecialchars($errorMsg));
      error_log("Product update error: SQL=$sql, Error=$errorMsg");
      header('Location: product-edit.php?id=' . $productId);
      exit;
    }
  }
  
  if ($action === 'delete') {
    $orderCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM order_items WHERE product_id=$productId");
    $hasOrders = false;
    if ($orderCheck) {
      $row = mysqli_fetch_assoc($orderCheck);
      $hasOrders = (int)$row['count'] > 0;
    }
    
    if ($hasOrders) {
      flash_set('err', 'Cannot delete product: This product has existing orders. Products with order history cannot be deleted to maintain data integrity.');
      header('Location: product-edit.php?id=' . $productId);
      exit;
    }
    
    if (!empty($product['image']) && file_exists(__DIR__ . '/' . $product['image'])) {
      @unlink(__DIR__ . '/' . $product['image']);
    }
    
    try {
      $deleteResult = mysqli_query($conn, "DELETE FROM products WHERE id=$productId LIMIT 1");
      if ($deleteResult && mysqli_affected_rows($conn) > 0) {
        flash_set('msg', 'Product deleted successfully.');
        header('Location: products.php');
        exit;
      } else {
        throw new Exception(mysqli_error($conn));
      }
    } catch (Exception $e) {
      $errorMsg = $e->getMessage();
      if (strpos($errorMsg, 'foreign key constraint') !== false) {
        flash_set('err', 'Cannot delete product: This product is referenced in existing orders.');
      } else {
        flash_set('err', 'Failed to delete product: ' . htmlspecialchars($errorMsg));
      }
      header('Location: product-edit.php?id=' . $productId);
      exit;
    }
  }
}

$r = mysqli_query($conn, "SELECT * FROM products WHERE id = $productId LIMIT 1");
if ($r && mysqli_num_rows($r) === 1) {
  $product = mysqli_fetch_assoc($r);
} else {
  flash_set('err', 'Product not found.');
  header('Location: products.php');
  exit;
}

$currency = currency_get();
$img = $product['image'] ? $product['image'] : 'https://images.unsplash.com/photo-1526367790999-0150786686a2?auto=format&fit=crop&w=1200&q=80';
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Product | ISDN</title>
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
        <h2><?php echo icon('pen-to-square'); ?> Edit Product</h2>
        <a class="btn btn-ghost" href="products.php"><?php echo icon('arrow-left'); ?> Back to Products</a>
      </div>

      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

      <div class="grid cols-2" style="align-items:start; gap:20px;">
        <!-- Product Image Preview -->
        <section class="card pad lift" style="animation-delay:.06s">
          <div class="section-head">
            <h2><?php echo icon('image'); ?> Product Image</h2>
            <span class="muted">Current preview</span>
          </div>
          <div style="text-align:center; padding:20px;">
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" data-preview
                 style="width:100%; max-width:400px; height:auto; border-radius:18px; border:2px solid var(--border); box-shadow:var(--shadow-md);">
            <div class="muted" style="margin-top:12px; font-size:13px" id="image-status">
              <?php echo $product['image'] ? 'Current product image' : 'Default placeholder image'; ?>
            </div>
          </div>
        </section>

        <!-- Edit Form -->
        <section class="card pad lift" style="animation-delay:.12s">
          <div class="section-head">
            <h2><?php echo icon('gear'); ?> Product Details</h2>
            <span class="badge-status">ID: #<?php echo (int)$product['id']; ?></span>
          </div>

          <form class="form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            
            <div class="field">
              <label>Product Name *</label>
              <input class="input" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required placeholder="Enter product name">
            </div>

            <div class="grid cols-2">
              <div class="field">
                <label>Category *</label>
                <input class="input" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required placeholder="e.g. Grocery">
              </div>
              <div class="field">
                <label>RDC Location *</label>
                <input class="input" name="rdc_location" value="<?php echo htmlspecialchars($product['rdc_location']); ?>" required placeholder="e.g. Colombo RDC">
              </div>
            </div>

            <div class="grid cols-2">
              <div class="field">
                <label>Price (USD) *</label>
                <input class="input" name="price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars((string)$product['price']); ?>" required>
                <div class="muted" style="font-size:11px; margin-top:4px;">
                  Current: <span data-price="<?php echo htmlspecialchars((string)$product['price']); ?>"><?php echo htmlspecialchars(price_label($product['price'], $currency)); ?></span>
                </div>
              </div>
              <div class="field">
                <label>Stock Quantity *</label>
                <input class="input" name="stock" type="number" min="0" value="<?php echo (int)$product['stock']; ?>" required>
                <div class="muted" style="font-size:11px; margin-top:4px;">
                  <?php
                    $stock = (int)$product['stock'];
                    $stockClass = $stock <= 0 ? 'stock-out' : ($stock <= 10 ? 'stock-low' : 'stock-ok');
                    $stockLabel = $stock <= 0 ? 'Out of stock' : ($stock <= 10 ? 'Low stock' : 'In stock');
                  ?>
                  Status: <span class="stock-badge <?php echo $stockClass; ?>" style="font-size:11px; padding:4px 8px;"><?php echo $stockLabel; ?></span>
                </div>
              </div>
            </div>

            <div class="field">
              <label>Product Image</label>
              <?php if ($product['image']): ?>
                <div style="margin-bottom:10px; display:flex; align-items:center; gap:12px; padding:10px; background:var(--card2); border-radius:12px; border:1px solid var(--border);">
                  <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Current" style="width:60px; height:60px; object-fit:cover; border-radius:8px; border:2px solid var(--border);">
                  <div>
                    <div style="font-weight:700; font-size:13px;">Current Image</div>
                    <div class="muted" style="font-size:11px;">Upload new image to replace</div>
                  </div>
                </div>
              <?php endif; ?>
              <input class="input" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
              <div class="muted" style="font-size:11px; margin-top:4px;">
                <?php echo $product['image'] ? 'Select new image to preview and replace current' : 'Upload product image (JPG, PNG, WebP, max 3MB)'; ?>
              </div>
            </div>

            <div class="product-actions" style="margin-top:20px; padding-top:20px; border-top:2px solid var(--border);">
              <button class="btn btn-primary" type="submit" style="flex:1;">
                <?php echo icon('floppy-disk'); ?> Save Changes
              </button>
              <a class="btn btn-ghost" href="products.php" style="flex:1;">
                <?php echo icon('xmark'); ?> Cancel
              </a>
            </div>
          </form>

          <form method="post" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.\n\nNote: Products with existing orders cannot be deleted.');" style="margin-top:16px; padding-top:16px; border-top:2px solid var(--border);">
            <input type="hidden" name="action" value="delete">
            <button class="btn" type="submit" style="width:100%; background: linear-gradient(135deg, rgba(239,68,68,.95), rgba(220,38,38,.75)); border-color: rgba(239,68,68,.5); color:#fff;">
              <?php echo icon('trash'); ?> Delete Product
            </button>
          </form>
        </section>
      </div>

      <!-- Product Statistics -->
      <section class="card pad lift" style="margin-top:20px; animation-delay:.18s">
        <div class="section-head">
          <h2><?php echo icon('chart-line'); ?> Product Statistics</h2>
          <span class="muted">Order history</span>
        </div>
        <?php
          $statsQuery = mysqli_query($conn, "SELECT 
            COUNT(DISTINCT oi.order_id) as total_orders,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue
            FROM order_items oi
            WHERE oi.product_id = $productId");
          $stats = ['total_orders' => 0, 'total_sold' => 0, 'total_revenue' => 0.0];
          if ($statsQuery && $statsRow = mysqli_fetch_assoc($statsQuery)) {
            $stats = [
              'total_orders' => (int)$statsRow['total_orders'],
              'total_sold' => (int)$statsRow['total_sold'],
              'total_revenue' => (float)$statsRow['total_revenue']
            ];
          }
        ?>
        <div class="grid cols-3">
          <div>
            <div class="muted">Total Orders</div>
            <div style="font-size:28px; font-weight:1000; margin-top:6px;"><?php echo $stats['total_orders']; ?></div>
          </div>
          <div>
            <div class="muted">Units Sold</div>
            <div style="font-size:28px; font-weight:1000; margin-top:6px;"><?php echo $stats['total_sold']; ?></div>
          </div>
          <div>
            <div class="muted">Total Revenue</div>
            <div class="price" data-price="<?php echo htmlspecialchars((string)$stats['total_revenue']); ?>" style="font-size:28px; margin-top:6px;"><?php echo htmlspecialchars(price_label($stats['total_revenue'], $currency)); ?></div>
          </div>
        </div>
        <?php if ($stats['total_orders'] > 0): ?>
          <div class="alert" style="margin-top:16px; background:rgba(16,185,129,.1); border-color:rgba(16,185,129,.3);">
            <strong><?php echo icon('circle-info'); ?> Note:</strong> This product has been ordered <?php echo $stats['total_orders']; ?> time(s). It cannot be deleted to preserve order history.
          </div>
        <?php endif; ?>
      </section>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
  <script src="image-preview.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const fileInput = document.querySelector('input[name="image"]');
      const statusText = document.getElementById('image-status');
      if (fileInput && statusText) {
        fileInput.addEventListener('change', function() {
          if (this.files && this.files.length > 0) {
            statusText.textContent = 'New image preview (click Save to apply)';
            statusText.style.color = 'var(--primary)';
            statusText.style.fontWeight = '700';
          } else {
            statusText.textContent = '<?php echo $product['image'] ? 'Current product image' : 'Default placeholder image'; ?>';
            statusText.style.color = '';
            statusText.style.fontWeight = '';
          }
        });
      }
    });
  </script>
</body>
</html>
