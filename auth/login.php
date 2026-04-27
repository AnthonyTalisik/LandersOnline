<?php
session_start();
require_once '../config/db.php';

if (!isset($_POST['login'])) {
    header('Location: /LandersOnline/index.php');
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password']   ?? '';

// 1. Find account
$stmt = $conn->prepare("
    SELECT Acct_Id, Acct_Email, Acct_Password, Acct_Role, Acct_Status, Acct_MustChangePw
    FROM   Accounts
    WHERE  Acct_Email = ?
    LIMIT  1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$acct = $stmt->get_result()->fetch_assoc();

if (!$acct) {
    $_SESSION['error']      = "No account found with that email.";
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

if ($acct['Acct_Status'] === 'inactive') {
    $_SESSION['error']      = "Your account has been deactivated.";
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

// 2. Password check
//    Acct_MustChangePw = 1 → password is plain text, compare with ===
//    Acct_MustChangePw = 0 → password is hashed, use password_verify()

$mustChange = (int)$acct['Acct_MustChangePw'] === 1;

if ($mustChange) {
    $passwordOk = ($password === $acct['Acct_Password']);
} else {
    $passwordOk = password_verify($password, $acct['Acct_Password']);
}

if (!$passwordOk) {
    $_SESSION['error']      = "Incorrect password.";
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

// 3. Set session
$_SESSION['account_id']     = $acct['Acct_Id'];
$_SESSION['role']           = $acct['Acct_Role'];
$_SESSION['email']          = $acct['Acct_Email'];
$_SESSION['must_change_pw'] = $mustChange;

// 4. Route by role
if ($acct['Acct_Role'] === 'admin') {

    $_SESSION['display_name'] = 'Admin';

    if ($mustChange) {
        // First login — force password setup
        header('Location: /LandersOnline/auth/set_password.php');
        exit();
    }

    $_SESSION['success'] = "Welcome back, Admin!";
    header('Location: /LandersOnline/admin/dashboard.php');
    exit();

} else {

    $cq = $conn->prepare("
        SELECT Cust_Id, Cust_Name FROM Customers
        WHERE  Cust_AcctId = ? LIMIT 1
    ");
    $cq->bind_param("i", $acct['Acct_Id']);
    $cq->execute();
    $cust = $cq->get_result()->fetch_assoc();

    $_SESSION['customer_id']  = $cust['Cust_Id']  ?? null;
    $_SESSION['display_name'] = $cust['Cust_Name'] ?? explode('@', $email)[0];
    $_SESSION['success']      = "Welcome back, " . $_SESSION['display_name'] . "!";
    header('Location: /LandersOnline/customer/dashboard.php');
    exit();
}
?>