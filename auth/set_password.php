<?php
// ================================================================
//  auth/set_password.php
//  Updates password in Firebase Auth + clears mustChangePw in Firestore
// ================================================================
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ob_start();
session_start();

define('FIREBASE_WEB_API_KEY', 'AIzaSyA-LnwB3b1LIYbtm2PWvMJWx92cvdpdauk');
define('FIREBASE_PROJECT_ID',  'landersonline-66e95');

// Only for logged-in admins who must change their password
if (
    !isset($_SESSION['account_id']) ||
    $_SESSION['role'] !== 'admin'   ||
    empty($_SESSION['must_change_pw'])
) {
    header('Location: /LandersOnline/index.php');
    exit();
}

$uid   = $_SESSION['account_id'];   // Firebase UID
$email = $_SESSION['email'] ?? '';
$error = '';

// ── Helper: cURL POST ────────────────────────────────────────────
function fbPost(string $url, array $payload, string $token = ''): array {
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $resp  = curl_exec($ch);
    $errno = curl_errno($ch);
    $err   = curl_error($ch);
    curl_close($ch);

    if ($errno) return ['__error' => "Network error ($errno): $err"];
    $decoded = json_decode($resp, true);
    return is_array($decoded) ? $decoded : ['__error' => 'Invalid response'];
}

// ── Helper: Firestore PATCH (update single field) ────────────────
function firestorePatch(string $uid, array $fields, string $projectId, string $token): bool {
    // Build field mask + fields payload
    $fieldMask  = implode(',', array_keys($fields));
    $fsFields   = [];
    foreach ($fields as $k => $v) {
        if (is_bool($v))    $fsFields[$k] = ['booleanValue' => $v];
        elseif (is_int($v)) $fsFields[$k] = ['integerValue' => (string)$v];
        else                $fsFields[$k] = ['stringValue'  => (string)$v];
    }

    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}"
         . "?updateMask.fieldPaths=" . urlencode($fieldMask);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "Authorization: Bearer $token"],
        CURLOPT_POSTFIELDS     => json_encode(['fields' => $fsFields]),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

if (isset($_POST['set_password'])) {
    $newPw  = $_POST['new_password']      ?? '';
    $confPw = $_POST['confirm_password']  ?? '';
    $tempPw = $_POST['temp_password']     ?? ''; // We need current password to get a fresh idToken

    // ── Validate ─────────────────────────────────────────────────
    if (strlen($newPw) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $newPw)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $newPw)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/\d/', $newPw)) {
        $error = "Password must contain at least one number.";
    } elseif ($newPw !== $confPw) {
        $error = "Passwords do not match.";
    } elseif (empty($tempPw)) {
        $error = "Please enter your current (temporary) password.";
    } else {
        // ── Step 1: Re-authenticate to get fresh idToken ──────────
        // Firebase requires a valid idToken to change password.
        // The session idToken from login may have expired, so re-sign in.
        $signIn = fbPost(
            'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . FIREBASE_WEB_API_KEY,
            ['email' => $email, 'password' => $tempPw, 'returnSecureToken' => true]
        );

        if (isset($signIn['__error']) || isset($signIn['error'])) {
            $error = "Current password is incorrect. Please try again.";
        } else {
            $idToken = $signIn['idToken'] ?? '';

            // ── Step 2: Update password in Firebase Auth ──────────
            $updateResp = fbPost(
                'https://identitytoolkit.googleapis.com/v1/accounts:update?key=' . FIREBASE_WEB_API_KEY,
                ['idToken' => $idToken, 'password' => $newPw, 'returnSecureToken' => true]
            );

            if (isset($updateResp['__error']) || isset($updateResp['error'])) {
                $errMsg = $updateResp['error']['message'] ?? $updateResp['__error'] ?? 'Unknown error';
                $error  = "Failed to update password: $errMsg";
            } else {
                // ── Step 3: Clear mustChangePw in Firestore ───────
                $newToken = $updateResp['idToken'] ?? $idToken; // use refreshed token
                firestorePatch($uid, ['mustChangePw' => false], FIREBASE_PROJECT_ID, $newToken);

                // ── Step 4: Update session and redirect ───────────
                $_SESSION['must_change_pw'] = false;
                $_SESSION['success']        = "Password updated! Welcome to LandersOnline Admin.";
                header('Location: /LandersOnline/admin/dashboard.php');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set Your Password — LandersOnline</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --g1:#2d5a0e; --g2:#3d7a18; --g3:#5a9e2a; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, var(--g1) 0%, var(--g3) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 40px 36px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
        }
        .logo { display:flex;align-items:center;gap:8px;justify-content:center;margin-bottom:28px; }
        .logo-circle {
            width:44px;height:44px;background:var(--g2);border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            color:#fff;font-weight:900;font-size:22px;
        }
        .logo-text { font-size:24px;font-weight:800;color:var(--g1);letter-spacing:2px; }
        h4 { font-weight:700;color:var(--g1);margin-bottom:6px; }
        .subtitle { font-size:13px;color:#888;margin-bottom:24px; }
        .l-input {
            width:100%;padding:11px 14px;
            border:1.5px solid #ddd;border-radius:8px;
            font-size:14px;margin-bottom:14px;
            outline:none;transition:border .2s;
        }
        .l-input:focus { border-color:var(--g2); }
        .pw-wrap { position:relative; }
        .pw-eye { position:absolute;right:13px;top:13px;cursor:pointer;color:#bbb;font-size:16px; }
        .btn-green {
            width:100%;background:var(--g2);color:#fff;
            border:none;padding:13px;border-radius:8px;
            font-size:15px;font-weight:700;cursor:pointer;
            transition:background .2s;margin-top:4px;
        }
        .btn-green:hover { background:var(--g1); }
        .pw-rules { list-style:none;padding:0;margin:0 0 16px;font-size:12px; }
        .pw-rules li { padding:3px 0;color:#bbb;display:flex;align-items:center;gap:6px; }
        .pw-rules li.ok { color:var(--g2); }
        .alert-err {
            background:#fff0f0;border:1.5px solid #f5c0c0;
            color:#c0392b;border-radius:8px;
            padding:10px 14px;font-size:13px;margin-bottom:16px;
        }
        .badge-first {
            background:#fff8e1;border:1.5px solid #f5a623;
            color:#b7700a;border-radius:8px;
            padding:10px 14px;font-size:13px;margin-bottom:20px;
            display:flex;align-items:center;gap:8px;
        }
        .section-label {
            font-size:11px;font-weight:700;color:#888;
            text-transform:uppercase;letter-spacing:1px;
            margin-bottom:8px;margin-top:4px;
        }
        .divider {
            border:none;border-top:1.5px solid #eee;margin:16px 0;
        }
    </style>
</head>
<body>
<div class="card">

    <div class="logo">
        <div class="logo-circle">L</div>
        <span class="logo-text">LANDERS</span>
    </div>

    <div class="badge-first">
        <i class="bi bi-shield-lock-fill" style="font-size:18px;"></i>
        <div>
            <strong>First time logging in!</strong><br>
            Set a secure password to continue to the Admin Panel.
        </div>
    </div>

    <h4>Set Your Password</h4>
    <p class="subtitle">
        Logged in as <strong><?= htmlspecialchars($email) ?></strong>
    </p>

    <?php if ($error): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">

        <!-- Current (temporary) password -->
        <p class="section-label">Current (Temporary) Password</p>
        <div class="pw-wrap">
            <input type="password" name="temp_password" id="tempPw"
                   class="l-input" placeholder="Your temporary password" required>
            <i class="bi bi-eye-slash pw-eye" onclick="tpw('tempPw', this)"></i>
        </div>

        <!-- New password -->
        <p class="section-label">New Password</p>
        <div class="pw-wrap">
            <input type="password" name="new_password" id="newPw"
                   class="l-input" placeholder="New Password" required
                   oninput="checkRules(this.value)">
            <i class="bi bi-eye-slash pw-eye" onclick="tpw('newPw', this)"></i>
        </div>

        <ul class="pw-rules">
            <li id="r-up"><i class="bi bi-circle"></i> Min. one uppercase letter</li>
            <li id="r-lo"><i class="bi bi-circle"></i> Min. one lowercase letter</li>
            <li id="r-nu"><i class="bi bi-circle"></i> At least one number</li>
            <li id="r-ln"><i class="bi bi-circle"></i> Eight or more characters</li>
            <li id="r-mt"><i class="bi bi-circle"></i> Passwords match</li>
        </ul>

        <div class="pw-wrap">
            <input type="password" name="confirm_password" id="confPw"
                   class="l-input" placeholder="Confirm New Password" required
                   oninput="checkMatch()">
            <i class="bi bi-eye-slash pw-eye" onclick="tpw('confPw', this)"></i>
        </div>

        <button type="submit" name="set_password" class="btn-green">
            <i class="bi bi-lock-fill me-2"></i>Set Password & Enter Admin Panel
        </button>

    </form>
</div>

<script>
    function tpw(id, icon) {
        const i = document.getElementById(id);
        i.type = i.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('bi-eye-slash');
        icon.classList.toggle('bi-eye');
    }
    function setRule(id, ok) {
        const el = document.getElementById(id);
        el.classList.toggle('ok', ok);
        el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    }
    function checkRules(v) {
        setRule('r-up', /[A-Z]/.test(v));
        setRule('r-lo', /[a-z]/.test(v));
        setRule('r-nu', /\d/.test(v));
        setRule('r-ln', v.length >= 8);
        checkMatch();
    }
    function checkMatch() {
        const pw   = document.getElementById('newPw').value;
        const conf = document.getElementById('confPw').value;
        setRule('r-mt', pw && conf && pw === conf);
    }
</script>
</body>
</html>