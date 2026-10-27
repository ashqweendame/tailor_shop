<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$error_message = '';

// ==========================================
// 1. DATABASE CONNECTION CONFIGURATION
// ==========================================
include("../database/database.php");

// ==========================================
// 2. FORM ACTION HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // --- SECURE AUTHENTICATION / ROLE-BASED ACCESS GATE ---
  if (isset($_POST['auth_action']) && $_POST['auth_action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
      // Query looking exclusively at username to determine authentic user presence and profile state
      $stmt = $conn->prepare("SELECT * FROM Employee WHERE username = ? LIMIT 1");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();
      $employee = $result->fetch_assoc();
      $stmt->close();

      if ($employee && password_verify($password, $employee['password'])) {
        session_regenerate_id(true);
        $_SESSION['username']  = $employee['username'];
        $_SESSION['user_role'] = $employee['role'];
        $_SESSION['employee_id'] = $employee['employee_id'];
        $_SESSION['firstname'] = $employee['firstname'];
        $_SESSION['lastname'] = $employee['lastname'];

        // Dynamic Router: Routes based on exact string match from database 'role' data matrix
        $role = strtolower(trim($employee['role']));

        if ($role === 'tailor') {
          header("Location: ../tailor/tailor.php");
          exit;
        } elseif ($role === 'service') {
          header("Location: ../service/service.php");
          exit;
        } elseif ($role === 'admin') {
          header("Location: ../admin/admin.php");
          exit;
        } else {
          // Fallback if role text in database doesn't map cleanly to the 3 structures
          $error_message = "Your designated system profile role routing rule is unconfigured.";
        }
      } else {
        $error_message = "Invalid credentials. Please try again.";
      }
    } else {
      $error_message = "Please fill in all verification fields.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Swift Tailoring · Identity Authentication Gate</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../customer/index.css?v=<?= time(); ?>" />
  <link rel="stylesheet" href="login.css?v=<?= time(); ?>" />
</head>

<body>

  <nav class="nav">
    <div class="nav-inner">
      <a class="nav-brand" href="../customer/index.php">
        <div class="nav-logo-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="6" cy="6" r="3" />
            <circle cx="6" cy="18" r="3" />
            <line x1="20" y1="4" x2="8.12" y2="15.88" />
            <line x1="14.47" y1="14.48" x2="20" y2="20" />
            <line x1="8.12" y1="8.12" x2="12" y2="12" />
          </svg>
        </div>
        <span class="nav-brand-name">Swift Tailoring</span>
      </a>
      <div style="display: flex; gap: 16px; align-items: center;">
        <a href="../customer/index.php" class="btn-login" style="background: transparent; color: var(--muted); padding: 0;">Track Orders</a>
      </div>
    </div>
  </nav>

  <div class="auth-container">

    <?php if (!empty($error_message)): ?>
      <div class="error-banner">
        <?= htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <div class="auth-card">

      <div class="auth-header">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 24px; color: var(--ink); margin-bottom: 4px;">Welcome Back</h1>
        <p style="color: var(--muted); font-size: 13px;">Swift Tailoring Access Gateway</p>
      </div>

      <div class="auth-body">
        <form action="login.php" method="POST">
          <input type="hidden" name="auth_action" value="login" />

          <div class="field-group">
            <label class="field-label">Username</label>
            <input type="text" name="username" class="form-input" placeholder="Enter workspace handle" required />
          </div>

          <div class="field-group" style="margin-bottom: 24px;">
            <label class="field-label">Password</label>
            <input type="password" name="password" class="form-input" placeholder="••••••••" required />
          </div>

          <button type="submit" class="btn-action-block">Sign In to Workspace</button>
        </form>
      </div>

      <div class="contact-footer-accent" style="padding: 24px 16px; border-radius: 0; border-bottom: none; background-color: var(--lavender);">
        <div class="contact-brand-accent" style="border: none; padding-top: 0;">
          <p class="brand-script" style="font-size: 18px; color: var(--violet);">Swift Tailoring</p>
          <p class="brand-tagline" style="font-size: 10px; margin-bottom: 0; color: var(--muted);">Elegance In Motion</p>
        </div>
      </div>

    </div>
  </div>

</body>

</html>