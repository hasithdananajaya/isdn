<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

if (is_logged_in()) {
  header('Location: dashboard.php');
  exit;
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
  $success = 'Account created successfully. Please login.';
}
if (isset($_GET['logged_out'])) {
  $success = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = esc($conn, $_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    $error = 'Please enter your username and password.';
  } else {
    $sql = "SELECT id, username, role, name, email, phone, rdc_location, profile_image
            FROM users
            WHERE username = '$username' AND password = MD5('" . esc($conn, $password) . "')
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) === 1) {
      $user = mysqli_fetch_assoc($res);
      $_SESSION['user'] = $user;
      redirect_by_role($user['role']);
    } else {
      $error = 'Invalid credentials. Please try again.';
    }
  }
}
$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | ISDN</title>
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
      <div class="grid cols-2" style="align-items:stretch">
        <section class="card pad lift" style="animation-delay:.08s">
          <div class="section-head">
            <h2><?php echo icon('right-to-bracket'); ?> Sign in</h2>
            <span class="muted">Secure access</span>
          </div>

          <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>

          <form class="form" method="post" autocomplete="on">
            <div class="field">
              <label for="username">Username</label>
              <input class="input" id="username" name="username" placeholder="e.g. admin" required>
            </div>

            <div class="field">
              <label for="password">Password</label>
              <div class="input-wrap">
                <input class="input" id="password" name="password" type="password" placeholder="Your password" required>
                <button class="toggle-eye" type="button" data-toggle-password="#password" aria-label="Show password">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>

            <button class="btn btn-primary" type="submit"><?php echo icon('unlock-keyhole'); ?> Login</button>
            <div class="muted" style="font-size:13px">
              New customer? <a href="signup.php" style="font-weight:900">Create an account</a>
            </div>
          </form>
        </section>

        <aside class="card pad lift" style="animation-delay:.16s">
          <div class="section-head">
            <h2><?php echo icon('sparkles'); ?> Test accounts</h2>
          </div>
          <div class="timeline">
            <div class="timeline-item done">
              <div class="timeline-dot"><?php echo icon('user-shield'); ?></div>
              <div>
                <div style="font-weight:1000">Admin</div>
                <div class="muted">Username: <strong>admin</strong> • Password: <strong>admin123</strong></div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"><?php echo icon('warehouse'); ?></div>
              <div>
                <div style="font-weight:1000">RDC Staff</div>
                <div class="muted">Username: <strong>rdc_staff</strong> • Password: <strong>rdc123</strong></div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"><?php echo icon('store'); ?></div>
              <div>
                <div style="font-weight:1000">Customer</div>
                <div class="muted">Username: <strong>customer</strong> • Password: <strong>customer123</strong></div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>

  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>

