<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'isdn';

$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
  if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    die('Database connection failed: ' . mysqli_connect_error() . '. Please check db.php settings.');
  } else {
    die('Database connection failed. Please contact administrator.');
  }
}
mysqli_set_charset($conn, 'utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function esc($conn, $val) {
  return mysqli_real_escape_string($conn, trim((string)$val));
}

function is_logged_in() {
  return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

function current_user() {
  return is_logged_in() ? $_SESSION['user'] : null;
}

function require_login() {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}

function require_role($role) {
  require_login();
  $u = current_user();
  if (!$u || $u['role'] !== $role) {
    header('Location: dashboard.php?err=unauthorized');
    exit;
  }
}

function require_any_role($roles) {
  require_login();
  $u = current_user();
  if (!$u) {
    header('Location: login.php');
    exit;
  }
  if (!in_array($u['role'], $roles, true)) {
    header('Location: dashboard.php?err=unauthorized');
    exit;
  }
}

function redirect_by_role($role) {
  header('Location: dashboard.php');
  exit;
}

function flash_set($key, $message) {
  $_SESSION['flash'][$key] = $message;
}

function flash_get($key) {
  if (!isset($_SESSION['flash'][$key])) return null;
  $msg = $_SESSION['flash'][$key];
  unset($_SESSION['flash'][$key]);
  return $msg;
}

function cart_get() {
  if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }
  return $_SESSION['cart'];
}

function cart_count() {
  $cart = cart_get();
  $sum = 0;
  foreach ($cart as $qty) $sum += (int)$qty;
  return $sum;
}

function currency_get() {
  if (isset($_GET['set_currency']) && in_array($_GET['set_currency'], ['USD','LKR'], true)) {
    $_SESSION['currency'] = $_GET['set_currency'];
    return $_GET['set_currency'];
  }
  return isset($_SESSION['currency']) && in_array($_SESSION['currency'], ['USD','LKR'], true)
    ? $_SESSION['currency'] : 'USD';
}

function currency_set($cur) {
  if (in_array($cur, ['USD','LKR'], true)) {
    $_SESSION['currency'] = $cur;
  }
}

function usd_to_lkr($usd) {
  return (float)$usd * 320.0;
}
