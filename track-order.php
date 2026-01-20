<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

require_login();
$u = current_user();
$role = $u['role'];

$orderId = (int)($_GET['order_id'] ?? 0);
$order = null;
$delivery = null;
$items = [];

if ($orderId > 0) {
  $sql = "SELECT o.*, u.name customer_name, u.email customer_email
          FROM orders o
          LEFT JOIN users u ON o.customer_id = u.id
          WHERE o.id = $orderId";
  if ($role === 'customer') {
    $sql .= " AND o.customer_id = " . (int)$u['id'];
  }
  $r = mysqli_query($conn, $sql . " LIMIT 1");
  if ($r && mysqli_num_rows($r) === 1) {
    $order = mysqli_fetch_assoc($r);
    $r2 = mysqli_query($conn, "SELECT * FROM deliveries WHERE order_id = $orderId LIMIT 1");
    if ($r2 && mysqli_num_rows($r2) === 1) {
      $delivery = mysqli_fetch_assoc($r2);
    }
    $r3 = mysqli_query($conn, "SELECT oi.*, p.name product_name, p.rdc_location
                               FROM order_items oi
                               JOIN products p ON oi.product_id = p.id
                               WHERE oi.order_id = $orderId");
    if ($r3) while ($row = mysqli_fetch_assoc($r3)) $items[] = $row;
  }
}

if (!$order) {
  header('Location: orders.php');
  exit;
}

$rdcLocation = $items ? $items[0]['rdc_location'] : 'Colombo RDC';
$customerLocation = ['lat' => 6.9271, 'lng' => 79.8612];
$rdcCoords = [
  'Colombo RDC' => ['lat' => 6.9271, 'lng' => 79.8612],
  'Kandy RDC' => ['lat' => 7.2906, 'lng' => 80.6337],
  'Galle RDC' => ['lat' => 6.0329, 'lng' => 80.2170],
];
$rdcPos = $rdcCoords[$rdcLocation] ?? $rdcCoords['Colombo RDC'];
$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Track Order | ISDN</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="ecommerce-styles.css">
  <style>
    .map { height: 420px; border-radius: var(--radius-xl); overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-md); }
  </style>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>
  <main class="main">
    <div class="container">
      <div class="section-head">
        <h2><?php echo icon('location-dot'); ?> Track Order #<?php echo (int)$order['id']; ?></h2>
        <span class="badge-status status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo icon('truck-fast'); ?> <?php echo ucfirst($order['status']); ?></span>
      </div>

      <div class="grid cols-2" style="align-items:start; margin-bottom:16px">
        <section class="card pad lift" style="animation-delay:.06s">
          <div class="section-head">
            <h2><?php echo icon('route'); ?> Delivery timeline</h2>
            <span class="muted">Status updates</span>
          </div>
          <div class="timeline">
            <div class="timeline-item <?php echo $order['status'] !== 'pending' ? 'done' : ''; ?>">
              <div class="timeline-dot"><?php echo icon('clock'); ?></div>
              <div>
                <div style="font-weight:1000">Order placed</div>
                <div class="muted"><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></div>
              </div>
            </div>
            <?php if ($delivery && $delivery['assigned_date']): ?>
              <div class="timeline-item <?php echo in_array($order['status'], ['dispatched','delivered'], true) ? 'done' : ''; ?>">
                <div class="timeline-dot"><?php echo icon('user-check'); ?></div>
                <div>
                  <div style="font-weight:1000">Assigned to RDC staff</div>
                  <div class="muted"><?php echo date('M d, Y H:i', strtotime($delivery['assigned_date'])); ?></div>
                </div>
              </div>
            <?php endif; ?>
            <?php if (in_array($order['status'], ['dispatched','delivered'], true)): ?>
              <div class="timeline-item <?php echo $order['status'] === 'delivered' ? 'done' : ''; ?>">
                <div class="timeline-dot"><?php echo icon('truck-fast'); ?></div>
                <div>
                  <div style="font-weight:1000">Dispatched</div>
                  <div class="muted">On the way to customer</div>
                </div>
              </div>
            <?php endif; ?>
            <?php if ($order['status'] === 'delivered' && $delivery && $delivery['delivered_date']): ?>
              <div class="timeline-item done">
                <div class="timeline-dot"><?php echo icon('circle-check'); ?></div>
                <div>
                  <div style="font-weight:1000">Delivered</div>
                  <div class="muted"><?php echo date('M d, Y H:i', strtotime($delivery['delivered_date'])); ?></div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </section>

        <section class="card pad lift" style="animation-delay:.12s">
          <div class="section-head">
            <h2><?php echo icon('clipboard-list'); ?> Order details</h2>
            <span class="muted"><?php echo count($items); ?> items</span>
          </div>
          <div class="timeline">
            <?php foreach ($items as $item): ?>
              <div class="timeline-item">
                <div class="timeline-dot"><?php echo icon('box'); ?></div>
                <div>
                  <div style="font-weight:1000"><?php echo htmlspecialchars($item['product_name']); ?></div>
                  <div class="muted">Qty: <?php echo (int)$item['quantity']; ?> • From: <?php echo htmlspecialchars($item['rdc_location']); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <section class="card pad lift" style="animation-delay:.18s">
        <div class="section-head">
          <h2><?php echo icon('map'); ?> Delivery route</h2>
          <span class="muted">Interactive map (Leaflet)</span>
        </div>
        <div id="map" class="map"></div>
      </section>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
  <script>
    const rdcLat = <?php echo $rdcPos['lat']; ?>;
    const rdcLng = <?php echo $rdcPos['lng']; ?>;
    const customerLat = <?php echo $customerLocation['lat']; ?>;
    const customerLng = <?php echo $customerLocation['lng']; ?>;
    const status = '<?php echo htmlspecialchars($order['status']); ?>';

    const map = L.map('map').setView([(rdcLat + customerLat) / 2, (rdcLng + customerLng) / 2], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19
    }).addTo(map);
    
    map.on('tileerror', function(error, tile) {
      console.warn('Tile loading error:', error);
    });

    const rdcMarker = L.marker([rdcLat, rdcLng]).addTo(map)
      .bindPopup('<strong><?php echo htmlspecialchars($rdcLocation); ?></strong><br>RDC Location')
      .openPopup();

    const customerMarker = L.marker([customerLat, customerLng], { icon: L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png', iconSize: [25, 41], iconAnchor: [12, 41] }) }).addTo(map)
      .bindPopup('<strong>Delivery Address</strong><br>Customer location');

    if (status === 'dispatched' || status === 'delivered') {
      const midLat = rdcLat + (customerLat - rdcLat) * 0.6;
      const midLng = rdcLng + (customerLng - rdcLng) * 0.6;
      L.marker([midLat, midLng], { icon: L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png', iconSize: [25, 41], iconAnchor: [12, 41] }) }).addTo(map)
        .bindPopup('<strong>Delivery Vehicle</strong><br>In transit');
    }

    if (status === 'dispatched' || status === 'delivered') {
      const polyline = L.polyline([[rdcLat, rdcLng], [customerLat, customerLng]], {
        color: '#667eea',
        weight: 4,
        dashArray: '15, 10',
        opacity: 0.8
      }).addTo(map);
      
      let offset = 0;
      setInterval(() => {
        offset = (offset + 5) % 25;
        polyline.setStyle({ dashOffset: -offset });
      }, 200);
    }

    function updateTracking() {
      fetch('api-track.php?order_id=<?php echo (int)$orderId; ?>')
        .then(res => res.json())
        .then(data => {
          if (data.vehicle_coords && data.vehicle_coords !== vehicleMarker) {
            console.log('Tracking update:', data);
          }
        })
        .catch(err => console.warn('Tracking API error:', err));
    }
    
    if (status === 'dispatched') {
      setInterval(updateTracking, 30000);
    }
  </script>
</body>
</html>
