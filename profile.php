<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

require_login();
$u = current_user();
$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

function ensure_dir($path) {
  if (!is_dir($path)) @mkdir($path, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'update_profile') {
    $name = esc($conn, $_POST['name'] ?? '');
    $email = esc($conn, $_POST['email'] ?? '');
    $phone = esc($conn, $_POST['phone'] ?? '');
    $rdc_location = esc($conn, $_POST['rdc_location'] ?? '');

    if ($name === '' || $email === '') {
      flash_set('err', 'Name and email are required.');
      header('Location: profile.php');
      exit;
    }

    $imagePath = null;
    if (!empty($_FILES['profile_image']['name']) && isset($_FILES['profile_image']['tmp_name'])) {
      $tmp = $_FILES['profile_image']['tmp_name'];
      $size = (int)($_FILES['profile_image']['size'] ?? 0);
      $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','webp'];
      if ($size > 3 * 1024 * 1024) {
        flash_set('err', 'Image too large (max 3MB).');
        header('Location: profile.php');
        exit;
      }
      if (!in_array($ext, $allowed, true)) {
        flash_set('err', 'Invalid image type. Use JPG/PNG/WebP.');
        header('Location: profile.php');
        exit;
      }
      ensure_dir(__DIR__ . '/uploads/profiles');
      $fname = 'u_' . (int)$u['id'] . '_' . time() . '.' . $ext;
      $dest = __DIR__ . '/uploads/profiles/' . $fname;
      if (move_uploaded_file($tmp, $dest)) {
        $imagePath = 'uploads/profiles/' . $fname;
        if ($u['profile_image'] && file_exists(__DIR__ . '/' . $u['profile_image'])) {
          @unlink(__DIR__ . '/' . $u['profile_image']);
        }
      }
    }

    $id = (int)$u['id'];
    $imgSql = $imagePath ? ", profile_image='" . esc($conn, $imagePath) . "'" : "";
    $rdcSql = ($u['role'] === 'rdc' && $rdc_location !== '') ? ", rdc_location='$rdc_location'" : "";
    $sql = "UPDATE users SET name='$name', email='$email', phone='$phone' $imgSql $rdcSql WHERE id=$id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
      $_SESSION['user']['name'] = $name;
      $_SESSION['user']['email'] = $email;
      $_SESSION['user']['phone'] = $phone;
      if ($imagePath) $_SESSION['user']['profile_image'] = $imagePath;
      if ($rdcSql) $_SESSION['user']['rdc_location'] = $rdc_location;
      flash_set('msg', 'Profile updated successfully.');
    } else {
      flash_set('err', 'Failed to update profile.');
    }
    header('Location: profile.php');
    exit;
  }

  if ($action === 'change_password') {
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
      flash_set('err', 'Please fill all password fields.');
      header('Location: profile.php');
      exit;
    }

    if (strlen($new) < 6) {
      flash_set('err', 'New password must be at least 6 characters.');
      header('Location: profile.php');
      exit;
    }

    if ($new !== $confirm) {
      flash_set('err', 'New passwords do not match.');
      header('Location: profile.php');
      exit;
    }

    $id = (int)$u['id'];
    $chk = mysqli_query($conn, "SELECT id FROM users WHERE id=$id AND password = MD5('" . esc($conn, $current) . "') LIMIT 1");
    if (!$chk || mysqli_num_rows($chk) !== 1) {
      flash_set('err', 'Current password is incorrect.');
      header('Location: profile.php');
      exit;
    }

    $sql = "UPDATE users SET password = MD5('" . esc($conn, $new) . "') WHERE id=$id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
      flash_set('msg', 'Password changed successfully.');
    } else {
      flash_set('err', 'Failed to change password.');
    }
    header('Location: profile.php');
    exit;
  }
}

$r = mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$u['id'] . " LIMIT 1");
if ($r) {
  $u = mysqli_fetch_assoc($r);
  if (!isset($u['created_at']) || empty($u['created_at'])) {
    $u['created_at'] = date('Y-m-d H:i:s');
  }
}
$profileImg = $u['profile_image'] ? $u['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($u['name']) . '&size=200&background=667eea&color=fff';
$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile | ISDN</title>
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
        <h2><?php echo icon('user'); ?> My Profile</h2>
        <span class="pill"><?php echo strtoupper($u['role']); ?></span>
      </div>

      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

      <div class="grid cols-2" style="align-items:start">
        <section class="card pad lift" style="animation-delay:.06s">
          <div class="section-head">
            <h2><?php echo icon('pen-to-square'); ?> Update profile</h2>
            <span class="muted">Personal information</span>
          </div>

          <div style="text-align:center; margin-bottom:16px">
            <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile" data-preview style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--border);box-shadow:var(--shadow-md)">
          </div>

          <form class="form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            <div class="field">
              <label>Profile picture</label>
              <input class="input" name="profile_image" type="file" accept=".jpg,.jpeg,.png,.webp">
              <div class="muted" style="font-size:11px; margin-top:4px;">Select an image to preview it above</div>
            </div>
            <div class="field">
              <label>Full name</label>
              <input class="input" name="name" value="<?php echo htmlspecialchars($u['name']); ?>" required>
            </div>
            <div class="field">
              <label>Email</label>
              <input class="input" name="email" type="email" value="<?php echo htmlspecialchars($u['email']); ?>" required>
            </div>
            <div class="field">
              <label>Phone</label>
              <input class="input" name="phone" value="<?php echo htmlspecialchars($u['phone'] ?: ''); ?>">
            </div>
            <?php if ($u['role'] === 'rdc'): ?>
              <div class="field">
                <label>RDC location</label>
                <input class="input" name="rdc_location" value="<?php echo htmlspecialchars($u['rdc_location'] ?: ''); ?>">
              </div>
            <?php endif; ?>
            <button class="btn btn-primary" type="submit"><?php echo icon('floppy-disk'); ?> Save changes</button>
          </form>
        </section>

        <section class="card pad lift" style="animation-delay:.12s">
          <div class="section-head">
            <h2><?php echo icon('lock'); ?> Change password</h2>
            <span class="muted">Security</span>
          </div>

          <form class="form" method="post">
            <input type="hidden" name="action" value="change_password">
            <div class="field">
              <label>Current password</label>
              <div class="input-wrap">
                <input class="input" id="current_password" name="current_password" type="password" required>
                <button class="toggle-eye" type="button" data-toggle-password="#current_password" aria-label="Show password">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="field">
              <label>New password</label>
              <div class="input-wrap">
                <input class="input" id="new_password" name="new_password" type="password" required>
                <button class="toggle-eye" type="button" data-toggle-password="#new_password" aria-label="Show password">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="field">
              <label>Confirm new password</label>
              <div class="input-wrap">
                <input class="input" id="confirm_password" name="confirm_password" type="password" required>
                <button class="toggle-eye" type="button" data-toggle-password="#confirm_password" aria-label="Show password">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>
            <button class="btn btn-primary" type="submit"><?php echo icon('key'); ?> Change password</button>
          </form>

          <div class="timeline" style="margin-top:20px">
            <div class="timeline-item done">
              <div class="timeline-dot"><?php echo icon('user'); ?></div>
              <div>
                <div style="font-weight:1000">Account info</div>
                <div class="muted">Username: <strong><?php echo htmlspecialchars($u['username']); ?></strong></div>
                <div class="muted">Role: <strong><?php echo strtoupper($u['role']); ?></strong></div>
                <div class="muted">Joined: <strong><?php echo isset($u['created_at']) && $u['created_at'] ? date('M d, Y', strtotime($u['created_at'])) : 'N/A'; ?></strong></div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
  <script src="image-preview.js"></script>
</body>
</html>
