<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

require_role('admin');

$msg = flash_get('msg') ?: '';
$err = flash_get('err') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add' || $action === 'edit') {
    $name = esc($conn, $_POST['name'] ?? '');
    $username = esc($conn, $_POST['username'] ?? '');
    $email = esc($conn, $_POST['email'] ?? '');
    $phone = esc($conn, $_POST['phone'] ?? '');
    $role = esc($conn, $_POST['role'] ?? 'customer');
    $rdc_location = esc($conn, $_POST['rdc_location'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($name === '' || $username === '' || $email === '' || !in_array($role, ['admin','rdc','customer'], true)) {
      flash_set('err', 'Please fill all required fields.');
      header('Location: users.php');
      exit;
    }

    if ($action === 'add') {
      if ($password === '') {
        flash_set('err', 'Password required for new users.');
        header('Location: users.php');
        exit;
      }
      $chk = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email' LIMIT 1");
      if ($chk && mysqli_num_rows($chk) > 0) {
        flash_set('err', 'Username or email already exists.');
        header('Location: users.php');
        exit;
      }
      $rdcSql = ($role === 'rdc' && $rdc_location !== '') ? "'$rdc_location'" : "NULL";
      $sql = "INSERT INTO users (username, password, role, name, email, phone, rdc_location)
              VALUES ('$username', MD5('" . esc($conn, $password) . "'), '$role', '$name', '$email', '$phone', $rdcSql)";
      if (mysqli_query($conn, $sql)) flash_set('msg', 'User added successfully.');
      else flash_set('err', 'Failed to add user.');
    } else {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) {
        flash_set('err', 'Invalid user selected.');
        header('Location: users.php');
        exit;
      }
      $chk = mysqli_query($conn, "SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != $id LIMIT 1");
      if ($chk && mysqli_num_rows($chk) > 0) {
        flash_set('err', 'Username or email already exists.');
        header('Location: users.php');
        exit;
      }
      $rdcSql = ($role === 'rdc' && $rdc_location !== '') ? "rdc_location = '$rdc_location'" : "rdc_location = NULL";
      $passSql = $password !== '' ? ", password = MD5('" . esc($conn, $password) . "')" : "";
      $sql = "UPDATE users SET name='$name', username='$username', email='$email', phone='$phone', role='$role', $rdcSql $passSql WHERE id=$id LIMIT 1";
      if (mysqli_query($conn, $sql)) flash_set('msg', 'User updated successfully.');
      else flash_set('err', 'Failed to update user.');
    }
    header('Location: users.php');
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0 && $id !== (int)$_SESSION['user']['id']) {
      mysqli_query($conn, "DELETE FROM users WHERE id=$id LIMIT 1");
      flash_set('msg', 'User deleted.');
    } else {
      flash_set('err', 'Cannot delete your own account.');
    }
    header('Location: users.php');
    exit;
  }
}

$users = [];
$r = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $users[] = $row;
$currency = currency_get();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? htmlspecialchars($_SESSION['theme']) : 'light'; ?>" data-currency="<?php echo htmlspecialchars($currency); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users | ISDN</title>
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
        <h2><?php echo icon('users'); ?> User Management</h2>
        <span class="muted"><?php echo count($users); ?> user(s)</span>
      </div>

      <?php if ($msg): ?><div class="alert success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

      <section class="card pad lift" style="margin-bottom:16px; animation-delay:.06s">
        <div class="section-head">
          <h2><?php echo icon('user-plus'); ?> Add user</h2>
          <span class="muted">Admin, RDC, or Customer</span>
        </div>
        <form class="form" method="post">
          <input type="hidden" name="action" value="add">
          <div class="grid cols-2">
            <div class="field">
              <label>Full name</label>
              <input class="input" name="name" required>
            </div>
            <div class="field">
              <label>Username</label>
              <input class="input" name="username" required>
            </div>
          </div>
          <div class="grid cols-2">
            <div class="field">
              <label>Email</label>
              <input class="input" name="email" type="email" required>
            </div>
            <div class="field">
              <label>Phone</label>
              <input class="input" name="phone">
            </div>
          </div>
          <div class="grid cols-2">
            <div class="field">
              <label>Role</label>
              <select class="select" name="role" required>
                <option value="customer">Customer</option>
                <option value="rdc">RDC Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="field">
              <label>RDC location (if RDC role)</label>
              <input class="input" name="rdc_location" placeholder="e.g. Colombo RDC">
            </div>
          </div>
          <div class="field">
            <label>Password</label>
            <div class="input-wrap">
              <input class="input" name="password" type="password" id="add_password" required>
              <button class="toggle-eye" type="button" data-toggle-password="#add_password" aria-label="Show password">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
          <button class="btn btn-primary" type="submit"><?php echo icon('sparkles'); ?> Add user</button>
        </form>
      </section>

      <section class="card pad lift" style="animation-delay:.12s">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>RDC Location</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $usr): ?>
              <tr>
                <td><strong>#<?php echo (int)$usr['id']; ?></strong></td>
                <td style="font-weight:1000"><?php echo htmlspecialchars($usr['name']); ?></td>
                <td><?php echo htmlspecialchars($usr['username']); ?></td>
                <td class="muted"><?php echo htmlspecialchars($usr['email']); ?></td>
                <td><span class="badge-status"><?php echo strtoupper($usr['role']); ?></span></td>
                <td class="muted"><?php echo htmlspecialchars($usr['rdc_location'] ?: 'N/A'); ?></td>
                <td class="muted" style="font-size:12px"><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></td>
                <td>
                  <details style="display:inline-block">
                    <summary class="btn btn-mini btn-ghost"><?php echo icon('pen-to-square'); ?> Edit</summary>
                    <div style="margin-top:6px; padding:10px; background:var(--card2); border-radius:14px; border:1px solid var(--border)">
                      <form class="form" method="post">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo (int)$usr['id']; ?>">
                        <div class="grid cols-2">
                          <input class="input" name="name" value="<?php echo htmlspecialchars($usr['name']); ?>" required>
                          <input class="input" name="username" value="<?php echo htmlspecialchars($usr['username']); ?>" required>
                        </div>
                        <div class="grid cols-2">
                          <input class="input" name="email" type="email" value="<?php echo htmlspecialchars($usr['email']); ?>" required>
                          <input class="input" name="phone" value="<?php echo htmlspecialchars($usr['phone'] ?: ''); ?>">
                        </div>
                        <div class="grid cols-2">
                          <select class="select" name="role" required>
                            <option value="customer" <?php echo $usr['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="rdc" <?php echo $usr['role'] === 'rdc' ? 'selected' : ''; ?>>RDC Staff</option>
                            <option value="admin" <?php echo $usr['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                          </select>
                          <input class="input" name="rdc_location" value="<?php echo htmlspecialchars($usr['rdc_location'] ?: ''); ?>" placeholder="RDC location">
                        </div>
                        <div class="field">
                          <label>New password (leave blank to keep current)</label>
                          <div class="input-wrap">
                            <input class="input" name="password" type="password" id="edit_password_<?php echo (int)$usr['id']; ?>">
                            <button class="toggle-eye" type="button" data-toggle-password="#edit_password_<?php echo (int)$usr['id']; ?>" aria-label="Show password">
                              <i class="fa-solid fa-eye"></i>
                            </button>
                          </div>
                        </div>
                        <button class="btn btn-primary btn-mini" type="submit"><?php echo icon('floppy-disk'); ?> Save</button>
                      </form>
                      <?php if ((int)$usr['id'] !== (int)$_SESSION['user']['id']): ?>
                        <form method="post" onsubmit="return confirm('Delete user <?php echo htmlspecialchars($usr['name']); ?>?');" style="margin-top:8px">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo (int)$usr['id']; ?>">
                          <button class="btn btn-mini" type="submit" style="background: linear-gradient(135deg, rgba(231,76,60,.95), rgba(155,89,182,.75));">
                            <?php echo icon('trash'); ?> Delete
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>
  <script src="theme-currency.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>
