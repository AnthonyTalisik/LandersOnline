<?php
// ================================================================
//  auth/set_password.php
//
//  Shown to admin accounts that still have Acct_MustChangePw = 1.
//  Takes the new password, hashes it with password_hash(),
//  saves it, sets Acct_MustChangePw = 0, then redirects to admin dashboard.
// ================================================================

session_start();
require_once '../config/db.php';

// Only accessible to logged-in admins who must change their password
if (
    !isset($_SESSION['account_id']) ||
    $_SESSION['role'] !== 'admin'   ||
    empty($_SESSION['must_change_pw'])
) {
    header('Location: /landers3/index.php');
    exit();
}

$acctId = $_SESSION['account_id'];
$error  = '';

if (isset($_POST['set_password'])) {

    $newPw  = $_POST['new_password']     ?? '';
    $confPw = $_POST['confirm_password'] ?? '';

    // Validate
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
    } else {
        // Hash and save
        $hashed = password_hash($newPw, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE Accounts
            SET    Acct_Password     = ?,
                   Acct_MustChangePw = 0
            WHERE  Acct_Id = ?
        ");
        $stmt->bind_param("si", $hashed, $acctId);

        if ($stmt->execute()) {
            // Clear the flag from session
            $_SESSION['must_change_pw'] = false;
            $_SESSION['success']        = "Password set successfully! Welcome to LandersOnline.";
            header('Location: /LandersOnline/admin/dashboard.php');
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
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
        :root { --g1:#2d5a0e; --g2:#3d7a18; --g3:#5a9e2a; --bd:#cce5a0; }
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
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-bottom: 28px;
        }
        .logo-circle {
            width: 44px; height: 44px;
            background: var(--g2); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 900; font-size: 22px;
        }
        .logo-text { font-size: 24px; font-weight: 800; color: var(--g1); letter-spacing: 2px; }
        h4 { font-weight: 700; color: var(--g1); margin-bottom: 6px; }
        .subtitle { font-size: 13px; color: #888; margin-bottom: 24px; }
        .l-input {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #ddd; border-radius: 8px;
            font-size: 14px; margin-bottom: 14px;
            outline: none; transition: border .2s;
        }
        .l-input:focus { border-color: var(--g2); }
        .pw-wrap { position: relative; }
        .pw-eye { position: absolute; right: 13px; top: 13px; cursor: pointer; color: #bbb; font-size: 16px; }
        .btn-green {
            width: 100%; background: var(--g2); color: #fff;
            border: none; padding: 13px; border-radius: 8px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: background .2s; margin-top: 4px;
        }
        .btn-green:hover { background: var(--g1); }
        .pw-rules { list-style: none; padding: 0; margin: 0 0 16px; font-size: 12px; }
        .pw-rules li { padding: 3px 0; color: #bbb; display: flex; align-items: center; gap: 6px; }
        .pw-rules li.ok { color: var(--g2); }
        .alert-err {
            background: #fff0f0; border: 1.5px solid #f5c0c0;
            color: #c0392b; border-radius: 8px;
            padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
        }
        .badge-first {
            background: #fff8e1; border: 1.5px solid #f5a623;
            color: #b7700a; border-radius: 8px;
            padding: 10px 14px; font-size: 13px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
    </style>
</head>
<body>
    <div class="card">

        <!-- Logo -->
        <div class="logo">
            <div class="logo-circle">L</div>
            <span class="logo-text">LANDERS</span>
        </div>

        <!-- Notice -->
        <div class="badge-first">
            <i class="bi bi-shield-lock-fill" style="font-size:18px;"></i>
            <div>
                <strong>First time logging in!</strong><br>
                Please set a secure password for your admin account.
            </div>
        </div>

        <h4>Set Your Password</h4>
        <p class="subtitle">This replaces your temporary password. Choose something strong.</p>

        <?php if ($error): ?>
        <div class="alert-err"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <!-- New password -->
            <div class="pw-wrap">
                <input type="password" name="new_password" id="newPw"
                       class="l-input" placeholder="New Password" required
                       oninput="checkRules(this.value)">
                <i class="bi bi-eye-slash pw-eye" onclick="tpw('newPw', this)"></i>
            </div>

            <!-- Rules -->
            <ul class="pw-rules">
                <li id="r-up"><i class="bi bi-circle"></i> Min. one uppercase letter</li>
                <li id="r-lo"><i class="bi bi-circle"></i> Min. one lowercase letter</li>
                <li id="r-nu"><i class="bi bi-circle"></i> At least one number</li>
                <li id="r-ln"><i class="bi bi-circle"></i> Eight or more characters</li>
                <li id="r-mt"><i class="bi bi-circle"></i> Passwords match</li>
            </ul>

            <!-- Confirm password -->
            <div class="pw-wrap">
                <input type="password" name="confirm_password" id="confPw"
                       class="l-input" placeholder="Confirm New Password" required
                       oninput="checkMatch()">
                <i class="bi bi-eye-slash pw-eye" onclick="tpw('confPw', this)"></i>
            </div>

            <button type="submit" name="set_password" class="btn-green">
                <i class="bi bi-lock-fill me-2"></i>Set Password & Continue
            </button>

        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
            el.querySelector('i').className = ok
                ? 'bi bi-check-circle-fill'
                : 'bi bi-circle';
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