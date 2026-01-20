<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
  echo json_encode(['error' => 'Invalid order ID']);
  exit;
}

$r = mysqli_query($conn, "SELECT o.*, d.status delivery_status, d.assigned_date, d.delivered_date
                          FROM orders o
                          LEFT JOIN deliveries d ON o.id = d.order_id
                          WHERE o.id = $orderId LIMIT 1");

if (!$r || mysqli_num_rows($r) !== 1) {
  echo json_encode(['error' => 'Order not found']);
  exit;
}

$data = mysqli_fetch_assoc($r);

$r2 = mysqli_query($conn, "SELECT p.rdc_location 
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = $orderId
                           LIMIT 1");
$rdcLocation = 'Colombo RDC';
if ($r2 && mysqli_num_rows($r2) > 0) {
  $row = mysqli_fetch_assoc($r2);
  $rdcLocation = $row['rdc_location'];
}

$rdcCoords = [
  'Colombo RDC' => ['lat' => 6.9271, 'lng' => 79.8612],
  'Kandy RDC' => ['lat' => 7.2906, 'lng' => 80.6337],
  'Galle RDC' => ['lat' => 6.0329, 'lng' => 80.2170],
  'Jaffna RDC' => ['lat' => 9.6615, 'lng' => 80.0255],
  'Batticaloa RDC' => ['lat' => 7.7102, 'lng' => 81.6924],
];
$rdcPos = $rdcCoords[$rdcLocation] ?? $rdcCoords['Colombo RDC'];

$customerPos = ['lat' => 6.9271, 'lng' => 79.8612];

$vehiclePos = null;
if (in_array($data['delivery_status'], ['dispatched', 'delivered'], true)) {
  $progress = $data['delivery_status'] === 'delivered' ? 1.0 : 0.6;
  $vehiclePos = [
    'lat' => $rdcPos['lat'] + ($customerPos['lat'] - $rdcPos['lat']) * $progress,
    'lng' => $rdcPos['lng'] + ($customerPos['lng'] - $rdcPos['lng']) * $progress,
  ];
}

echo json_encode([
  'order_id' => (int)$data['id'],
  'status' => $data['status'],
  'delivery_status' => $data['delivery_status'],
  'rdc_location' => $rdcLocation,
  'rdc_coords' => $rdcPos,
  'customer_coords' => $customerPos,
  'vehicle_coords' => $vehiclePos,
  'assigned_date' => $data['assigned_date'],
  'delivered_date' => $data['delivered_date'],
  'timestamp' => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);
