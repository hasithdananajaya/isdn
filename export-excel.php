<?php
require_once __DIR__ . '/db.php';

require_role('admin');

$type = esc($conn, $_GET['type'] ?? 'orders');
$filename = 'isdn_' . $type . '_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

$currency = currency_get();
$currencyLabel = $currency === 'LKR' ? ' (LKR)' : ' (USD)';

if ($type === 'orders') {
  fputcsv($output, ['Order ID', 'Customer Name', 'Customer Email', 'Total' . $currencyLabel, 'Status', 'Order Date']);
  $r = mysqli_query($conn, "SELECT o.*, u.name customer_name, u.email customer_email
                            FROM orders o
                            LEFT JOIN users u ON o.customer_id = u.id
                            ORDER BY o.order_date DESC");
  if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
      $total = (float)$row['total'];
      if ($currency === 'LKR') {
        $total = $total * 320;
      }
      fputcsv($output, [
        (int)$row['id'],
        $row['customer_name'] ?: 'N/A',
        $row['customer_email'] ?: 'N/A',
        number_format($total, 2, '.', ''),
        $row['status'],
        $row['order_date']
      ]);
    }
  }
} elseif ($type === 'products') {
  fputcsv($output, ['Product ID', 'Name', 'Category', 'Price' . $currencyLabel, 'Stock', 'RDC Location', 'Created']);
  $r = mysqli_query($conn, "SELECT * FROM products ORDER BY created_at DESC");
  if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
      $price = (float)$row['price'];
      if ($currency === 'LKR') {
        $price = $price * 320;
      }
      fputcsv($output, [
        (int)$row['id'],
        $row['name'],
        $row['category'],
        number_format($price, 2, '.', ''),
        (int)$row['stock'],
        $row['rdc_location'],
        $row['created_at']
      ]);
    }
  }
} elseif ($type === 'users') {
  fputcsv($output, ['User ID', 'Username', 'Name', 'Email', 'Phone', 'Role', 'RDC Location', 'Created']);
  $r = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
  if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
      fputcsv($output, [
        (int)$row['id'],
        $row['username'],
        $row['name'],
        $row['email'],
        $row['phone'] ?: 'N/A',
        $row['role'],
        $row['rdc_location'] ?: 'N/A',
        $row['created_at']
      ]);
    }
  }
} else {
  fputcsv($output, ['Error', 'Invalid export type']);
}

fclose($output);
exit;
