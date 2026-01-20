<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

if (is_logged_in()) {
  header('Location: dashboard.php');
  exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = esc($conn, $_POST['name'] ?? '');
  $username = esc($conn, $_POST['username'] ?? '');
  $email = esc($conn, $_POST['email'] ?? '');
  $phone = esc($conn, $_POST['phone'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $confirm = (string)($_POST['confirm_password'] ?? '');

  if ($name === '' || $username === '' || $email === '' || $password === '' || $confirm === '') {
    $error = 'Please fill in all required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } else {
    $chk = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email' LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
      $error = 'Username or email is already in use.';
    } else {
      $sql = "INSERT INTO users (username,password,role,name,email,phone,rdc_location,profile_image)
              VALUES ('$username', MD5('" . esc($conn, $password) . "'), 'customer', '$name', '$email', '$phone', NULL, NULL)";
      if (mysqli_query($conn, $sql)) {
        header('Location: login.php?registered=1');
        exit;
      } else {
        $error = 'Signup failed. Please try again.';
      }
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
  <title>Sign Up | ISDN</title>
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
      <section class="card pad lift" style="max-width:720px; margin: 0 auto; animation-delay:.08s">
        <div class="section-head">
          <h2><?php echo icon('user-plus'); ?> Create customer account</h2>
          <span class="muted">Retailer access</span>
        </div>

        <?php if ($success): ?>
          <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="form" method="post" autocomplete="on">
          <div class="grid cols-2">
            <div class="field">
              <label for="name">Full name</label>
              <input class="input" id="name" name="name" placeholder="Your name" required>
            </div>
            <div class="field">
              <label for="phone">Phone</label>
              <input class="input" id="phone" name="phone" placeholder="Optional">
            </div>
          </div>

          <div class="grid cols-2">
            <div class="field">
              <label for="username">Username</label>
              <input class="input" id="username" name="username" placeholder="Choose a username" required>
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input class="input" id="email" name="email" type="email" placeholder="you@example.com" required>
            </div>
          </div>

          <div class="grid cols-2">
            <div class="field">
              <label for="password">Password</label>
              <div class="input-wrap">
                <input class="input" id="password" name="password" type="password" placeholder="Minimum 6 characters" required>
                <button class="toggle-eye" type="button" data-toggle-password="#password" aria-label="Show password">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="field">
              <label for="confirm_password">Confirm password</label>
              <div class="input-wrap">
                <input class="input" id="confirm_password" name="confirm_password" type="password" placeholder="Repeat password" required>
                <button class="toggle-eye" type="button" data-toggle-password="#confirm_password" aria-label="Show password">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <button class="btn btn-primary" type="submit"><?php echo icon('sparkles'); ?> Create account</button>
          <div class="muted" style="font-size:13px">
            Already have an account? <a href="login.php" style="font-weight:900">Login</a>
          </div>
        </form>
      </section>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>

  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>

