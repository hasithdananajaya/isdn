<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

require_role('admin');

$currency = currency_get();

$revenueData = [];
for ($i = 29; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $r = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) s FROM orders WHERE DATE(order_date) = '$date'");
  $val = 0.0;
  if ($r) $val = (float)mysqli_fetch_assoc($r)['s'];
  $revenueData[] = ['date' => date('M d', strtotime($date)), 'value' => $val];
}

$categoryData = [];
$r = mysqli_query($conn, "SELECT p.category, SUM(oi.quantity * oi.price) total
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           GROUP BY p.category
                           ORDER BY total DESC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $categoryData[] = $row;

$labels = json_encode(array_column($revenueData, 'date'));
$revenueValues = json_encode(array_column($revenueData, 'value'));
$catLabels = json_encode(array_column($categoryData, 'category'));
$catValues = json_encode(array_column($categoryData, 'total'));
$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Advanced Analytics | ISDN</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="ecommerce-styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>
  <main class="main">
    <div class="container">
      <div class="section-head">
        <h2><?php echo icon('chart-pie'); ?> Advanced Analytics</h2>
        <div class="product-actions">
          <a class="btn btn-ghost" href="analytics.php"><?php echo icon('chart-line'); ?> Basic Analytics</a>
          <details style="display:inline-block">
            <summary class="btn btn-ghost"><?php echo icon('file-export'); ?> Export</summary>
            <div style="margin-top:6px; padding:10px; background:var(--card2); border-radius:14px; border:1px solid var(--border); position:absolute; z-index:10">
              <?php $currency = currency_get(); ?>
              <a class="btn btn-mini btn-primary" href="export-excel.php?type=orders" style="display:block; margin-bottom:6px; text-align:center"><?php echo icon('clipboard-list'); ?> Orders (<?php echo htmlspecialchars($currency); ?>)</a>
              <a class="btn btn-mini btn-primary" href="export-excel.php?type=products" style="display:block; margin-bottom:6px; text-align:center"><?php echo icon('boxes-stacked'); ?> Products (<?php echo htmlspecialchars($currency); ?>)</a>
              <a class="btn btn-mini btn-primary" href="export-excel.php?type=users" style="display:block; text-align:center"><?php echo icon('users'); ?> Users</a>
            </div>
          </details>
        </div>
      </div>

      <section class="card pad lift" style="margin-bottom:16px; animation-delay:.06s">
        <div class="section-head">
          <h2><?php echo icon('chart-line'); ?> Revenue trend (last 30 days)</h2>
          <span class="muted">USD base</span>
        </div>
        <div class="chart-wrap">
          <canvas id="revenueChart" style="max-height:400px"></canvas>
        </div>
      </section>

      <section class="card pad lift" style="animation-delay:.12s">
        <div class="section-head">
          <h2><?php echo icon('chart-pie'); ?> Sales by category</h2>
          <span class="muted">Pie chart</span>
        </div>
        <div class="chart-wrap">
          <canvas id="categoryChart" style="max-height:400px"></canvas>
        </div>
      </section>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
  <script>
    const labels = <?php echo $labels; ?>;
    const revenueData = <?php echo $revenueValues; ?>;
    const catLabels = <?php echo $catLabels; ?>;
    const catData = <?php echo $catValues; ?>;

    const ctx1 = document.getElementById('revenueChart');
    if (ctx1) {
      new Chart(ctx1, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Daily Revenue (USD)',
            data: revenueData,
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { mode: 'index', intersect: false }
          },
          scales: {
            y: { beginAtZero: true, ticks: { callback: (v) => '$' + v.toFixed(2) } }
          }
        }
      });
    }

    const ctx2 = document.getElementById('categoryChart');
    if (ctx2 && catLabels.length > 0) {
      const colors = [
        'rgba(102, 126, 234, 0.8)',
        'rgba(118, 75, 162, 0.8)',
        'rgba(240, 147, 251, 0.8)',
        'rgba(79, 172, 254, 0.8)',
        'rgba(0, 242, 254, 0.8)',
        'rgba(39, 174, 96, 0.8)',
        'rgba(243, 156, 18, 0.8)',
        'rgba(231, 76, 60, 0.8)'
      ];
      new Chart(ctx2, {
        type: 'pie',
        data: {
          labels: catLabels,
          datasets: [{
            data: catData,
            backgroundColor: colors.slice(0, catLabels.length),
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: { display: true, position: 'right' },
            tooltip: { callbacks: { label: (ctx) => ctx.label + ': $' + ctx.parsed.toFixed(2) } }
          }
        }
      });
    } else if (ctx2) {
      ctx2.parentElement.innerHTML = '<div class="muted" style="padding:20px;text-align:center">No category data available yet.</div>';
    }
  </script>
</body>
</html>
