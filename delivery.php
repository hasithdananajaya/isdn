<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

require_any_role(['admin', 'rdc']);
$u = current_user();
$role = $u['role'];

$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign') {
  $did = (int)($_POST['delivery_id'] ?? 0);
  $staffId = (int)($_POST['rdc_staff_id'] ?? 0);
  if ($did > 0 && $staffId > 0) {
    mysqli_query($conn, "UPDATE deliveries SET rdc_staff_id = $staffId, assigned_date = NOW() WHERE id = $did LIMIT 1");
    flash_set('msg', 'Delivery assigned successfully.');
  }
  header('Location: delivery.php');
  exit;
}

if (($_POST['action'] ?? '') === 'update_status') {
  $did = (int)($_POST['delivery_id'] ?? 0);
  $status = esc($conn, $_POST['status'] ?? '');
  if ($did > 0 && in_array($status, ['pending','dispatched','delivered'], true)) {
    if ($role === 'rdc') {
      $myId = (int)$u['id'];
      mysqli_query($conn, "UPDATE deliveries SET status = '$status' WHERE id = $did AND rdc_staff_id = $myId LIMIT 1");
    } else {
      mysqli_query($conn, "UPDATE deliveries SET status = '$status' WHERE id = $did LIMIT 1");
    }
    if ($status === 'dispatched') {
      mysqli_query($conn, "UPDATE deliveries SET assigned_date = COALESCE(assigned_date, NOW()) WHERE id = $did LIMIT 1");
    }
    if ($status === 'delivered') {
      mysqli_query($conn, "UPDATE deliveries SET delivered_date = NOW() WHERE id = $did LIMIT 1");
      mysqli_query($conn, "UPDATE orders SET status = 'delivered' WHERE id = (SELECT order_id FROM deliveries WHERE id = $did LIMIT 1) LIMIT 1");
    }
    flash_set('msg', 'Delivery status updated.');
  }
  header('Location: delivery.php');
  exit;
}

$where = [];
if ($role === 'rdc') {
  $where[] = "d.rdc_staff_id = " . (int)$u['id'];
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$deliveries = [];
$sql = "SELECT d.*, o.id order_id, o.total order_total, o.status order_status, o.order_date,
        u.name customer_name, u.email customer_email, u.phone customer_phone,
        staff.name staff_name, staff.rdc_location staff_rdc
        FROM deliveries d
        JOIN orders o ON d.order_id = o.id
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN users staff ON d.rdc_staff_id = staff.id
        $whereSql
        ORDER BY d.id DESC";
$r = mysqli_query($conn, $sql);
if ($r) while ($row = mysqli_fetch_assoc($r)) $deliveries[] = $row;

$currency = currency_get();

$rdcStaff = [];
if ($role === 'admin') {
  $r = mysqli_query($conn, "SELECT id, name, rdc_location FROM users WHERE role = 'rdc' ORDER BY name");
  if ($r) while ($row = mysqli_fetch_assoc($r)) $rdcStaff[] = $row;
}
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Deliveries | ISDN</title>
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
        <h2><?php echo icon('truck-fast'); ?> <?php echo $role === 'rdc' ? 'My Deliveries' : 'Delivery Management'; ?></h2>
        <span class="muted"><?php echo count($deliveries); ?> delivery(ies)</span>
      </div>

      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

      <?php if (!$deliveries): ?>
        <section class="card pad lift" style="animation-delay:.06s">
          <strong>No deliveries found.</strong>
          <div class="muted" style="margin-top:6px"><?php echo $role === 'rdc' ? 'No deliveries assigned to you yet.' : 'No deliveries in the system.'; ?></div>
        </section>
      <?php else: ?>
        <section class="card pad lift" style="animation-delay:.06s">
          <table>
            <thead>
              <tr>
                <th>Delivery ID</th>
                <th>Order #</th>
                <th>Customer</th>
                <th>Status</th>
                <?php if ($role === 'admin'): ?><th>Assigned to</th><?php endif; ?>
                <th>Timeline</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($deliveries as $d): ?>
                <?php
                  $status = $d['status'];
                  $statusClass = 'status-' . $status;
                  $statusIcon = $status === 'pending' ? 'clock' : ($status === 'dispatched' ? 'truck-fast' : 'circle-check');
                ?>
                <tr>
                  <td><strong>#<?php echo (int)$d['id']; ?></strong></td>
                  <td><a href="orders.php" style="font-weight:1000">#<?php echo (int)$d['order_id']; ?></a></td>
                  <td>
                    <div style="font-weight:1000"><?php echo htmlspecialchars($d['customer_name'] ?: 'N/A'); ?></div>
                    <div class="muted" style="font-size:12px"><?php echo htmlspecialchars($d['customer_email'] ?: ''); ?></div>
                  </td>
                  <td><span class="badge-status <?php echo $statusClass; ?>"><?php echo icon($statusIcon); ?> <?php echo ucfirst($status); ?></span></td>
                  <?php if ($role === 'admin'): ?>
                    <td>
                      <?php if ($d['staff_name']): ?>
                        <div style="font-weight:1000"><?php echo htmlspecialchars($d['staff_name']); ?></div>
                        <div class="muted" style="font-size:12px"><?php echo htmlspecialchars($d['staff_rdc'] ?: ''); ?></div>
                      <?php else: ?>
                        <details style="display:inline-block">
                          <summary class="btn btn-mini"><?php echo icon('user-plus'); ?> Assign</summary>
                          <div style="margin-top:6px; padding:10px; background:var(--card2); border-radius:14px; border:1px solid var(--border)">
                            <form method="post">
                              <input type="hidden" name="action" value="assign">
                              <input type="hidden" name="delivery_id" value="<?php echo (int)$d['id']; ?>">
                              <select class="select" name="rdc_staff_id" required style="margin-bottom:8px">
                                <option value="">Select RDC staff</option>
                                <?php foreach ($rdcStaff as $staff): ?>
                                  <option value="<?php echo (int)$staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['rdc_location'] ?: 'N/A'); ?>)</option>
                                <?php endforeach; ?>
                              </select>
                              <button class="btn btn-primary btn-mini" type="submit"><?php echo icon('floppy-disk'); ?> Assign</button>
                            </form>
                          </div>
                        </details>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                  <td>
                    <div class="timeline" style="gap:6px">
                      <?php if ($d['assigned_date']): ?>
                        <div class="timeline-item done" style="padding:8px">
                          <div class="timeline-dot" style="width:28px;height:28px"><?php echo icon('user-check'); ?></div>
                          <div style="font-size:12px">
                            <div style="font-weight:1000">Assigned</div>
                            <div class="muted"><?php echo date('M d, Y H:i', strtotime($d['assigned_date'])); ?></div>
                          </div>
                        </div>
                      <?php endif; ?>
                      <?php if ($d['delivered_date']): ?>
                        <div class="timeline-item done" style="padding:8px">
                          <div class="timeline-dot" style="width:28px;height:28px"><?php echo icon('circle-check'); ?></div>
                          <div style="font-size:12px">
                            <div style="font-weight:1000">Delivered</div>
                            <div class="muted"><?php echo date('M d, Y H:i', strtotime($d['delivered_date'])); ?></div>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <details style="display:inline-block">
                      <summary class="btn btn-mini btn-ghost"><?php echo icon('pen-to-square'); ?> Status</summary>
                      <div style="margin-top:6px; padding:10px; background:var(--card2); border-radius:14px; border:1px solid var(--border)">
                        <form method="post">
                          <input type="hidden" name="action" value="update_status">
                          <input type="hidden" name="delivery_id" value="<?php echo (int)$d['id']; ?>">
                          <select class="select" name="status" style="margin-bottom:8px">
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="dispatched" <?php echo $status === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                          </select>
                          <button class="btn btn-primary btn-mini" type="submit"><?php echo icon('floppy-disk'); ?> Update</button>
                        </form>
                      </div>
                    </details>
                    <a class="btn btn-mini btn-ghost" href="track-order.php?order_id=<?php echo (int)$d['order_id']; ?>"><?php echo icon('location-dot'); ?> Track</a>
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
