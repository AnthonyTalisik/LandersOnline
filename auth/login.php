<?php
// ================================================================
//  auth/login.php — Firebase REST API login (no heavy Admin SDK)
// ================================================================

// --- DEBUGGING SETTINGS ---
// IMPORTANT: REMOVE THESE LINES IN PRODUCTION!
error_reporting(E_ALL); // Show all errors
ini_set('display_errors', 1); // Display errors in the browser
ini_set('max_execution_time', '120'); // Increase timeout slightly for debugging
ini_set('memory_limit', '512M'); // Increase memory limit slightly for debugging
// --- END DEBUGGING SETTINGS ---

ob_start();
session_start();

$firebaseConfig = require __DIR__ . '/../config/firebase_store.php';
define('FIREBASE_WEB_API_KEY', $firebaseConfig['api_key']);
define('FIREBASE_PROJECT_ID',  $firebaseConfig['project_id']);

if (!isset($_POST['login'])) {
    header('Location: /LandersOnline/index.php');
    exit();
}

$email    = trim($_POST['email']    ?? '');
$password =      $_POST['password'] ?? '';

// DEBUG POINT: Initial input check
// echo "Debug: Email = " . htmlspecialchars($email) . ", Password = " . htmlspecialchars($password) . "<br>"; // Comment out or remove when debugging further
// exit(); // Uncomment to stop here and see input values

if (empty($email) || empty($password)) {
    $_SESSION['error']      = "Please enter both email and password.";
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

// ── Helper: call Firebase REST endpoint ─────────────────────────
function firebasePost(string $url, array $payload): array {
    $json = json_encode($payload);

    // Try cURL first
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false, // DANGEROUS IN PRODUCTION, but helps debug locally
            CURLOPT_SSL_VERIFYHOST => false, // DANGEROUS IN PRODUCTION, but helps debug locally
        ]);
        $resp  = curl_exec($ch);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            return ['__curl_error' => "cURL $errno: $err"];
        }
        return json_decode($resp, true) ?? ['error' => ['message' => 'Invalid JSON response']];
    }

    // Fallback: file_get_contents
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => 'Content-Type: application/json',
            'content'       => $json,
            'timeout'       => 20,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false], // DANGEROUS IN PRODUCTION, but helps debug locally
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        return ['__curl_error' => 'file_get_contents also failed — no internet from XAMPP'];
    }
    return json_decode($resp, true) ?? ['error' => ['message' => 'Invalid JSON response']];
}

// ── Helper: Firestore REST get document ─────────────────────────
function firestoreGetUser(string $uid, string $projectId, string $token): array {
    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false, // DANGEROUS IN PRODUCTION, but helps debug locally
        CURLOPT_SSL_VERIFYHOST => false, // DANGEROUS IN PRODUCTION, but helps debug locally
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true) ?? [];
}

// DEBUG POINT: Before Auth REST call
// exit("Debug: About to call Firebase Auth REST endpoint."); 

// ── STEP 1: Authenticate with Firebase REST ──────────────────────
$authUrl  = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . FIREBASE_WEB_API_KEY;
$authData = firebasePost($authUrl, [
    'email'             => $email,
    'password'          => $password,
    'returnSecureToken' => true,
]);

// DEBUG POINT: After Auth REST call
// echo "Debug: Firebase Auth Response: <pre>" . htmlspecialchars(print_r($authData, true)) . "</pre>";
// exit(); // Uncomment to stop here and see Firebase Auth response

// Network error
if (isset($authData['__curl_error'])) {
    error_log("Firebase network error: " . $authData['__curl_error']);
    $_SESSION['error']      = "Cannot reach authentication server. Check your internet connection or firewall/antivirus settings on your PC.";
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

// Firebase auth error
if (isset($authData['error'])) {
    $code = $authData['error']['message'] ?? '';
    $msg  = match(true) {
        str_contains($code, 'EMAIL_NOT_FOUND')          => "No account found with that email.",
        str_contains($code, 'INVALID_PASSWORD')          => "Incorrect password.",
        str_contains($code, 'INVALID_LOGIN_CREDENTIALS') => "Invalid email or password.",
        str_contains($code, 'USER_DISABLED')             => "Your account has been deactivated.",
        str_contains($code, 'TOO_MANY_ATTEMPTS')         => "Too many failed attempts. Try again later.",
        default                                           => "Login failed. Please try again. ($code)",
    };
    $_SESSION['error']      = $msg;
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

$uid   = $authData['localId']    ?? null;
$token = $authData['idToken']    ?? null;

if (!$uid || !$token) {
    $_SESSION['error']      = "Authentication error. Please try again.";
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

// DEBUG POINT: After successful Auth
// echo "Debug: Authenticated! UID = " . htmlspecialchars($uid) . ", Token = " . htmlspecialchars($token) . "<br>";
// exit(); // Uncomment to stop here and see UID/Token

// ── STEP 2: Fetch Firestore profile via REST (no Admin SDK needed) ──
$doc = firestoreGetUser($uid, FIREBASE_PROJECT_ID, $token);

// DEBUG POINT: After Firestore fetch
// echo "Debug: Firestore raw document response: <pre>" . htmlspecialchars(print_r($doc, true)) . "</pre>";
// exit(); // Uncomment to stop here and see raw Firestore response

// Parse Firestore REST response format
// Firestore REST returns: { "fields": { "role": { "stringValue": "admin" }, ... } }
$profile = [];
if (isset($doc['fields'])) {
    foreach ($doc['fields'] as $key => $val) {
        // Each field is { "stringValue": "x" } or { "booleanValue": true } etc.
        $profile[$key] = $val['stringValue']
                      ?? $val['booleanValue']
                      ?? $val['integerValue']
                      ?? $val['doubleValue']
                      ?? null;
    }
} else {
    // Profile doesn't exist in Firestore yet — still let them log in with basic info
    error_log("No Firestore profile for UID: $uid");
    // DEBUG POINT: If profile fields are missing in response
    // echo "Debug: Firestore profile fields missing for UID: " . htmlspecialchars($uid) . ". Raw Doc: <pre>" . htmlspecialchars(print_r($doc, true)) . "</pre>";
    // exit(); // Uncomment to stop here and see why profile is empty
}

// DEBUG POINT: After profile parsing
// echo "Debug: Parsed Profile: <pre>" . htmlspecialchars(print_r($profile, true)) . "</pre>";
// exit(); // Uncomment to stop here and see parsed profile

// App-level deactivation check
if (($profile['status'] ?? 'active') === 'inactive') {
    $_SESSION['error']      = "Your account has been deactivated. Please contact support.";
    $_SESSION['open_modal'] = 'login';
    header('Location: /LandersOnline/index.php');
    exit();
}

// DEBUG POINT: Before session setting
// exit("Debug: All checks passed. About to set session variables.");

// ── STEP 3: Set session ──────────────────────────────────────────
$_SESSION['account_id']     = $uid;
$_SESSION['customer_id']    = $uid;
$_SESSION['role']           = $profile['role']         ?? 'customer';
$_SESSION['email']          = $profile['email']        ?? $email;
$_SESSION['must_change_pw'] = (bool)($profile['mustChangePw'] ?? false);

$first = $profile['firstName'] ?? '';
$last  = $profile['lastName']  ?? '';
$name  = trim("$first $last");
$_SESSION['display_name']   = $name ?: explode('@', $email)[0];

// DEBUG POINT: After session setting, before redirect
// exit("Debug: Session variables set. About to route. Display Name: " . htmlspecialchars($_SESSION['display_name']));

// ── STEP 4: Route ────────────────────────────────────────────────
if ($_SESSION['role'] === 'admin') {
    if ($_SESSION['must_change_pw']) {
        header('Location: /LandersOnline/auth/set_password.php');
        exit();
    }
    $_SESSION['success'] = "Welcome back, Admin!";
    header('Location: /LandersOnline/admin/dashboard.php');
    exit();
}

$_SESSION['success'] = "Welcome back, {$_SESSION['display_name']}!";
header('Location: /LandersOnline/customer/dashboard.php');
exit();

?>
