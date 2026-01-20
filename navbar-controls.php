<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

$currency = currency_get();
$theme = isset($_SESSION['theme']) && in_array($_SESSION['theme'], ['light','dark'], true) ? $_SESSION['theme'] : 'light';

if (isset($_GET['set_currency'])) {
  currency_set($_GET['set_currency']);
  $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
  $queryParams = $_GET;
  unset($queryParams['set_currency']);
  $newQuery = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
  header('Location: ' . $currentUrl . $newQuery);
  exit;
}
if (isset($_GET['set_theme']) && in_array($_GET['set_theme'], ['light','dark'], true)) {
  $_SESSION['theme'] = $_GET['set_theme'];
  $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
  $queryParams = $_GET;
  unset($queryParams['set_theme']);
  $newQuery = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
  header('Location: ' . $currentUrl . $newQuery);
  exit;
}
?>

<div class="nav-controls">
  <div class="nav-control">
    <label class="sr-only" for="currencySelect">Currency</label>
    <select id="currencySelect" class="select luxury-select" data-currency-select>
      <option value="USD" <?php echo $currency === 'USD' ? 'selected' : ''; ?>>USD</option>
      <option value="LKR" <?php echo $currency === 'LKR' ? 'selected' : ''; ?>>LKR</option>
    </select>
  </div>
  <button type="button" class="btn btn-ghost btn-icon" data-theme-toggle aria-label="Toggle theme">
    <i class="fa-solid fa-moon"></i>
  </button>
</div>

