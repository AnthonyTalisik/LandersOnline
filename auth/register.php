<?php
// ================================================================
//  auth/register.php
//  Sign-up always creates Acct_Role = 'customer'.
//  Password is hashed immediately with password_hash().
// ================================================================

session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /LandersOnline/index.php'); exit();
}

$email    = trim($_POST['email']           ?? '');
$password = $_POST['password']             ?? '';
$confirm  = $_POST['confirm_password']     ?? '';
$fullname = trim($_POST['fullname']        ?? '');
$phone    = trim($_POST['phone']           ?? '');


// ── Validate ─────────────────────────────────────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Please enter a valid email address.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
    $_SESSION['error'] = "Password must be 8+ characters with uppercase, lowercase, and a number.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}
if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}
if (empty($fullname)) {
    $_SESSION['error'] = "Full name is required.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}

if (empty($phone)) {
    $_SESSION['error'] = "Phone number is required.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}

// ── Check duplicate email ─────────────────────────────────────────
$chk = $conn->prepare("SELECT Acct_Id FROM Accounts WHERE Acct_Email = ?");
$chk->bind_param("s", $email);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $_SESSION['error'] = "That email is already registered. Please log in instead.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}

// ── Insert Account (role = 'customer', password hashed) ──────────
$hashed = password_hash($password, PASSWORD_DEFAULT);

$s1 = $conn->prepare("
    INSERT INTO Accounts (Acct_Email, Acct_Password, Acct_Role, Acct_Status, Acct_MustChangePw)
    VALUES (?, ?, 'customer', 'active', 0)
");
$s1->bind_param("ss", $email, $hashed);

if (!$s1->execute()) {
    $_SESSION['error'] = "Registration failed. Please try again.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}



$newAcctId = $conn->insert_id;

// ── Insert Customer profile ───────────────────────────────────────
$s2 = $conn->prepare("
    INSERT INTO Customers (Cust_AcctId, Cust_Name, Cust_Phone)
    VALUES (?, ?, ?)
");


$s2->bind_param("iss", $newAcctId, $fullname, $phone);
$s2->execute();

$newCustId = $conn->insert_id;

// ── Auto-login ────────────────────────────────────────────────────
$_SESSION['account_id']   = $newAcctId;
$_SESSION['customer_id']  = $newCustId;
$_SESSION['role']         = 'customer';
$_SESSION['email']        = $email;
$_SESSION['display_name'] = $fullname;
$_SESSION['must_change_pw'] = false;

$_SESSION['success'] = "Welcome to LandersOnline, $fullname!";
header('Location: /LandersOnline/customer/dashboard.php');
exit();
?>