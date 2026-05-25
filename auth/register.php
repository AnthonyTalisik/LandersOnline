<?php
// ================================================================
//  auth/register.php — Firebase REST API registration (no heavy Admin SDK)
// ================================================================
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ob_start();
session_start();

define('FIREBASE_WEB_API_KEY', 'AIzaSyA-LnwB3b1LIYbtm2PWvMJWx92cvdpdauk');
define('FIREBASE_PROJECT_ID',  'landersonline-66e95');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /LandersOnline/index.php');
    exit();
}

$email    = trim($_POST['email']           ?? '');
$password =      $_POST['password']        ?? '';
$confirm  =      $_POST['confirm_password']?? '';
$fname    = trim($_POST['fname']           ?? '');
$lname    = trim($_POST['lname']           ?? '');
$phone    = trim($_POST['phone']           ?? '');

// ── Validation ───────────────────────────────────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error']      = "Please enter a valid email address.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
    $_SESSION['error']      = "Password must be 8+ characters with uppercase, lowercase, and a number.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}
if ($password !== $confirm) {
    $_SESSION['error']      = "Passwords do not match.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}
if (empty($fname) || empty($lname)) {
    $_SESSION['error']      = "First Name and Last Name are required.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}
if (empty($phone)) {
    $_SESSION['error']      = "Phone number is required.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}

// ── Helper: POST to Firebase REST ───────────────────────────────
function firebasePost(string $url, array $payload, string $token = ''): array {
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

    if ($errno) return ['__curl_error' => "cURL $errno: $err"];
    return json_decode($resp, true) ?? ['error' => ['message' => 'Invalid JSON']];
}

// ── Helper: Firestore PATCH (create/update document) ────────────
function firestoreSetUser(string $uid, array $data, string $projectId, string $token): bool {
    // Build Firestore REST field format
    $fields = [];
    foreach ($data as $k => $v) {
        if (is_bool($v))        $fields[$k] = ['booleanValue' => $v];
        elseif (is_int($v))     $fields[$k] = ['integerValue' => (string)$v];
        else                    $fields[$k] = ['stringValue'  => (string)$v];
    }

    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            "Authorization: Bearer $token",
        ],
        CURLOPT_POSTFIELDS     => json_encode(['fields' => $fields]),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code === 200;
}

// ── STEP 1: Create user in Firebase Auth via REST ────────────────
$signUpUrl  = 'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=' . FIREBASE_WEB_API_KEY;
$signUpData = firebasePost($signUpUrl, [
    'email'             => $email,
    'password'          => $password,
    'displayName'       => "$fname $lname",
    'returnSecureToken' => true,
]);

if (isset($signUpData['__curl_error'])) {
    error_log("Register cURL error: " . $signUpData['__curl_error']);
    $_SESSION['error']      = "Cannot reach authentication server. Check your internet/firewall settings.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}

if (isset($signUpData['error'])) {
    $code = $signUpData['error']['message'] ?? '';
    $msg  = match(true) {
        str_contains($code, 'EMAIL_EXISTS')    => "That email address is already registered.",
        str_contains($code, 'INVALID_EMAIL')   => "Please enter a valid email address.",
        str_contains($code, 'WEAK_PASSWORD')   => "Password is too weak.",
        default                                 => "Registration failed. ($code)",
    };
    $_SESSION['error']      = $msg;
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}

$uid   = $signUpData['localId'] ?? null;
$token = $signUpData['idToken'] ?? null;

if (!$uid || !$token) {
    $_SESSION['error']      = "Registration error. Please try again.";
    $_SESSION['open_modal'] = 'signup';
    header('Location: /LandersOnline/index.php'); exit();
}

// ── STEP 2: Save profile to Firestore via REST ───────────────────
$profileData = [
    'email'         => $email,
    'role'          => 'customer',
    'status'        => 'active',
    'mustChangePw'  => false,
    'firstName'     => $fname,
    'lastName'      => $lname,
    'phone'         => $phone,
    'createdAt'     => date('c'), // ISO 8601
];

firestoreSetUser($uid, $profileData, FIREBASE_PROJECT_ID, $token);
// Note: if Firestore write fails, user still logs in — profile will be sparse but fixable

// ── STEP 3: Auto-login ───────────────────────────────────────────
$_SESSION['account_id']     = $uid;
$_SESSION['customer_id']    = $uid;
$_SESSION['role']           = 'customer';
$_SESSION['email']          = $email;
$_SESSION['display_name']   = "$fname $lname";
$_SESSION['must_change_pw'] = false;

$_SESSION['success'] = "Welcome to LandersOnline, $fname!";
header('Location: /LandersOnline/customer/dashboard.php');
exit();