<?php
// login.php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['role'])) {
    header($_SESSION['role'] === 'traveller'
        ? 'Location: /tripistry/traveller/dashboard.php'
        : 'Location: /tripistry/agency/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = get_db();
        $stmt = $pdo->prepare(
            'SELECT account_ID, traveler_ID, agency_ID, agency_name,
                    username, password, role
             FROM User_Account WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['account_ID'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['username'] = $user['username'];

            if ($user['role'] === 'traveller') {
                $_SESSION['traveler_id'] = $user['traveler_ID'];
                header('Location: /tripistry/traveller/dashboard.php');
            } else {
                $_SESSION['agency_id']   = $user['agency_ID'];
                $_SESSION['agency_name'] = $user['agency_name'];
                header('Location: /tripistry/agency/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry — Sign In</title>
<link rel="stylesheet" href="/tripistry/css/login.css">
</head>
<body>

<div class="panel-left">
  <div class="brand">
    <div class="brand-name">Trip<span>istry</span></div>
  </div>
  <div class="panel-tagline">
    <h2>YOUR JOURNEY<br><em>STARTS HERE.</em></h2>
    <p>Browse curated travel packages, compare agencies, and book your next adventure — all in one place.</p>
  </div>
  <div class="panel-deco"></div>
</div>

<div class="panel-right">
  <div class="form-card">
    <h1>WELCOME BACK</h1>
    <p class="subtitle">Sign in to your account</p>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/tripistry/login.php" novalidate>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               placeholder="Enter your username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               autocomplete="username" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Enter your password"
               autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-submit">Sign In</button>
    </form>
  </div>
</div>

</body>
</html>