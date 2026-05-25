<?php
// ================================================================
//  firebase_test.php  — drop this in your LandersOnline/ root
//  Visit: http://localhost/LandersOnline/firebase_test.php
//  DELETE THIS FILE after you confirm login works!
// ================================================================
echo "<pre style='font-family:monospace;font-size:13px;'>";
echo "=== Firebase Connectivity Test ===\n\n";

// 1. PHP version
echo "PHP Version: " . PHP_VERSION . "\n";

// 2. cURL
echo "cURL enabled: " . (function_exists('curl_init') ? "YES" : "NO") . "\n";
if (function_exists('curl_version')) {
    $cv = curl_version();
    echo "cURL version: " . $cv['version'] . "\n";
    echo "SSL version:  " . $cv['ssl_version'] . "\n";
}

// 3. OpenSSL
echo "OpenSSL:      " . (extension_loaded('openssl') ? phpversion('openssl') : "NOT LOADED") . "\n\n";

// 4. Test outbound HTTPS to Firebase
echo "--- Testing outbound connection to Firebase ---\n";
$url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=AIzaSyA-LnwB3b1LIYbtm2PWvMJWx92cvdpdauk';

// Test with cURL + SSL verify OFF
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode(['email'=>'test@test.com','password'=>'wrongpassword','returnSecureToken'=>true]),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$resp = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
    echo "cURL FAILED  — errno $errno: $error\n";
    echo "\nThis means your XAMPP cannot reach Google's servers.\n";
    echo "Common causes:\n";
    echo "  • Windows Firewall blocking PHP/Apache\n";
    echo "  • Antivirus (Avast/Norton/Kaspersky) blocking HTTPS from XAMPP\n";
    echo "  • No internet connection from the machine running XAMPP\n";
    echo "  • Corporate/school network blocking outbound HTTPS\n\n";
    echo "Fix: Open Windows Defender Firewall → Allow Apache HTTP Server\n";
} else {
    $data = json_decode($resp, true);
    echo "cURL succeeded! HTTP $http\n";
    if (isset($data['error'])) {
        $msg = $data['error']['message'];
        echo "Firebase responded: $msg\n";
        if (str_contains($msg, 'INVALID_LOGIN_CREDENTIALS') || str_contains($msg, 'EMAIL_NOT_FOUND')) {
            echo "\n✅ CONNECTION WORKS — Firebase is reachable from your server!\n";
            echo "   Your login.php should work. The error above is just because test@test.com doesn't exist.\n";
        } elseif (str_contains($msg, 'API_KEY_INVALID')) {
            echo "\n❌ API KEY is wrong — double-check your Web API Key in Firebase Console.\n";
        }
    }
}

// 5. Vendor/autoload check
echo "\n--- Checking Composer autoload ---\n";
$autoload = __DIR__ . '/vendor/autoload.php';
echo "vendor/autoload.php exists: " . (file_exists($autoload) ? "YES" : "NO — run 'composer install'") . "\n";

// 6. Service account key check
$sak = __DIR__ . '/serviceAccountKey.json';
echo "serviceAccountKey.json exists: " . (file_exists($sak) ? "YES" : "NO") . "\n";

echo "\n=== Done ===\n</pre>";