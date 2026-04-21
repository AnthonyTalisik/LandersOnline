<?php
session_start();
require_once "../config/db.php";

// ══════════════════════════════
//  REGISTER
// ══════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'register') {

    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $cardType        = $_POST['card_type'] ?? '';
    $cardNumber      = trim($_POST['card_number'] ?? '');
    $cardName        = trim($_POST['card_name'] ?? '');

    // Validate
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email address.";
        $_SESSION['open_modal'] = 'signup';
        header("Location: /landersonline/index.php");
        exit();
    }

    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $_SESSION['error'] = "Password does not meet requirements.";
        $_SESSION['open_modal'] = 'signup';
        header("Location: /landersonline/index.php");
        exit();
    }

    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        $_SESSION['open_modal'] = 'signup';
        header("Location: /landersonline/index.php");
        exit();
    }

    // Check duplicate email
    $check = $conn->prepare("SELECT Acct_Id FROM Accounts WHERE Acct_Email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Email already registered.";
        $_SESSION['open_modal'] = 'signup';
        header("Location: /landersonline/index.php");
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Generate IDs
    $r1 = $conn->query("SELECT MAX(Acct_Id) AS max_id FROM Accounts");
    $acctId = ($r1->fetch_assoc()['max_id'] ?? 1000) + 1;

    $r2 = $conn->query("SELECT MAX(Cust_Id) AS max_id FROM Customers");
    $custId = ($r2->fetch_assoc()['max_id'] ?? 2000) + 1;

    // Insert Account
    $stmt = $conn->prepare("
        INSERT INTO Accounts (Acct_Id, Acct_Email, Acct_Password, Acct_Role, Acct_Status, Acct_MustChangePassword)
        VALUES (?, ?, ?, 'customer', 'active', FALSE)
    ");
    $stmt->bind_param("iss", $acctId, $email, $hashed);

    if (!$stmt->execute()) {
        $_SESSION['error'] = "Registration failed. Please try again.";
        $_SESSION['open_modal'] = 'signup';
        header("Location: /landersonline/index.php");
        exit();
    }

    // Insert Customer
    $stmt2 = $conn->prepare("
        INSERT INTO Customers (Cust_Id, Cust_AcctId, Cust_CardType, Cust_CardNumber, Cust_CardName)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param("iisss", $custId, $acctId, $cardType, $cardNumber, $cardName);
    $stmt2->execute();

    // Auto-login
    $_SESSION['account_id']  = $acctId;
    $_SESSION['role']        = 'customer';
    $_SESSION['email']       = $email;
    $_SESSION['display_name'] = explode('@', $email)[0];
    $_SESSION['customer_id'] = $custId;
    $_SESSION['cart_count']  = 0;

    $_SESSION['success'] = "Welcome to LandersOnline! Your account has been created.";
    header("Location: /landersonline/customer/dashboard.php");
    exit();
}


// ══════════════════════════════
//  LOGIN
// ══════════════════════════════
if (isset($_POST['login'])) {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM Accounts WHERE Acct_Email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Account not found.";
        $_SESSION['open_modal'] = 'login';
        header("Location: /landersonline/index.php");
        exit();
    }

    $account = $result->fetch_assoc();

    if ($account['Acct_Status'] === 'inactive') {
        $_SESSION['error'] = "Your account has been deactivated.";
        $_SESSION['open_modal'] = 'login';
        header("Location: /landersonline/index.php");
        exit();
    }

    if (!password_verify($password, $account['Acct_Password'])) {
        $_SESSION['error'] = "Incorrect password.";
        $_SESSION['open_modal'] = 'login';
        header("Location: /landersonline/index.php");
        exit();
    }

    // Set session
    $_SESSION['account_id'] = $account['Acct_Id'];
    $_SESSION['role']       = $account['Acct_Role'];
    $_SESSION['email']      = $account['Acct_Email'];

    // Force password change
    if ($account['Acct_MustChangePassword']) {
        header("Location: /landersonline/auth/change_password.php");
        exit();
    }

    // Role-based redirect
    if ($account['Acct_Role'] === 'admin') {
        $_SESSION['display_name'] = 'Admin';
        $_SESSION['success'] = "Welcome back, Admin!";
        header("Location: /landersonline/admin/dashboard.php");
        exit();
    }

    // Customer
    $cust = $conn->prepare("SELECT Cust_Id, Cust_Name FROM Customers WHERE Cust_AcctId = ? LIMIT 1");
    $cust->bind_param("i", $account['Acct_Id']);
    $cust->execute();
    $custData = $cust->get_result()->fetch_assoc();

    $_SESSION['customer_id']  = $custData['Cust_Id'] ?? null;
    $_SESSION['display_name'] = $custData['Cust_Name'] ?? explode('@', $email)[0];
    $_SESSION['cart_count']   = 0;

    // Restore cart count from DB
    $cartQ = $conn->prepare("SELECT SUM(Cart_Qty) as total FROM Cart WHERE Cart_AcctId = ?");
    $cartQ->bind_param("i", $account['Acct_Id']);
    $cartQ->execute();
    $cartR = $cartQ->get_result()->fetch_assoc();
    $_SESSION['cart_count'] = (int)($cartR['total'] ?? 0);

    $_SESSION['success'] = "Welcome back!";
    header("Location: /landersonline/customer/dashboard.php");
    exit();
}

// Fallback
header("Location: /landersonline/index.php");
exit();
?>
