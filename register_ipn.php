<?php
// === CONFIG ===
$base_url = 'https://cybqa.pesapal.com/pesapalv3'; // Sandbox

$consumer_key    = 'TDpigBOOhs+zAl8cwH2Fl82jJGyD8xev';     // e.g. qkio1BGGYAZTBtWk5rg8Xa18JT1IWaDR or from file
$consumer_secret = '1KpqkfsMaihIcOlhnBo/gBZ5smw=';  // from the same file

$ipn_url = 'http://ngo-nga.ct.ws/ipn_listener.php';  // ← Change this!

// === STEP 1: Get fresh access token ===
$token_url = "$base_url/api/Auth/RequestToken";
$token_payload = json_encode([
    'consumer_key'    => $consumer_key,
    'consumer_secret' => $consumer_secret
]);

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $token_payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
// Add these for full debug
curl_setopt($ch, CURLOPT_VERBOSE, true);  // Logs details to STDERR
$verbose_log = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose_log);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // Prevent hanging
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
rewind($verbose_log);
$verbose_output = stream_get_contents($verbose_log);
fclose($verbose_log);
curl_close($ch);

// === Print Full Debug ===
echo "<h3>Token Request Debug Output</h3>";
echo "Requested URL: $token_url<br>";
echo "HTTP Status Code: $http_code<br>";
if ($curl_error) {
    echo "cURL Error: $curl_error<br>";
}
echo "Verbose Log (connection details):<pre>" . htmlspecialchars($verbose_output) . "</pre>";
echo "Raw Response Body:<pre>" . htmlspecialchars($response ?: 'EMPTY RESPONSE') . "</pre>";

$token_data = json_decode($response, true);

if ($http_code === 200 && isset($token_data['token']) && !empty($token_data['token'])) {
    $access_token = $token_data['token'];
    echo "<h2 style='color:green'>SUCCESS! Token obtained.</h2>";
    echo "Token (partial): " . substr($access_token, 0, 20) . "...<br>";
    // Continue to IPN registration...

    // ================================================
// NOW REGISTER THE IPN USING THE FRESH $access_token
// ================================================

echo "<br><h3>Step 2: Registering your IPN...</h3>";

// Your public IPN URL (make sure ngrok is running!)
$ipn_url = 'http://ngo-nga.ct.ws/ipn_listener.php';

$register_url = "$base_url/api/URLSetup/RegisterIPN";

$ipn_payload = json_encode([
    'url'                   => $ipn_url,
    'ipn_notification_type' => 'POST'
]);

$ch = curl_init($register_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $ipn_payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

// IMPORTANT: Add these two lines for local testing (remove later!)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Debug helpers
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose_log_ipn = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose_log_ipn);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response_ipn = curl_exec($ch);
$http_code_ipn = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error_ipn = curl_error($ch);

rewind($verbose_log_ipn);
$verbose_output_ipn = stream_get_contents($verbose_log_ipn);
fclose($verbose_log_ipn);
curl_close($ch);

echo "<h4>IPN Registration Debug:</h4>";
echo "HTTP Status Code: $http_code_ipn<br>";
if ($curl_error_ipn) echo "cURL Error: $curl_error_ipn<br>";
echo "Verbose Log:<pre>" . htmlspecialchars($verbose_output_ipn) . "</pre>";
echo "Raw Response:<pre>" . htmlspecialchars($response_ipn ?: 'EMPTY RESPONSE') . "</pre>";

$result = json_decode($response_ipn, true);

if ($http_code_ipn === 200 && !empty($result['ipn_id'])) {
    echo "<h2 style='color: green'>IPN REGISTERED SUCCESSFULLY!</h2>";
    echo "<strong>Your notification_id (ipn_id):</strong><br>";
    echo "<div style='font-size: 1.3em; font-family: monospace; background: #e8ffe8; padding: 12px; border: 1px solid #0c0; margin: 10px 0;'>";
    echo $result['ipn_id'];
    echo "</div>";
    
    echo "<p>Copy the ID above and add it to your main payment file (process_payment.php) like this:</p>";
    echo "<pre style='background: #f8f8f8; padding: 12px; border: 1px solid #ccc;'>";
    echo "\$ipn_id = '" . $result['ipn_id'] . "';  // ← your real ID here\n";
    echo "</pre>";
} else {
    echo "<h2 style='color: red'>IPN Registration Failed</h2>";
    if (isset($result['message'])) {
        echo "Pesapal Message: " . $result['message'] . "<br>";
    }
    echo "HTTP Code: $http_code_ipn<br>";
}
} else {
    echo "<h2 style='color:red'>Token FAILED!</h2>";
    if (isset($token_data['message'])) {
        echo "Pesapal Error Message: " . $token_data['message'] . "<br>";
    }
    if (isset($token_data['status'])) {
        echo "Status Code from Pesapal: " . $token_data['status'] . "<br>";
    }
    if ($http_code == 401 || $http_code == 403) {
        echo "<strong>Likely cause:</strong> Credentials rejected (even with demo keys) or IP blocked from too many attempts. Try later or different network.<br>";
    } elseif ($http_code == 0) {
        echo "<strong>Likely cause:</strong> Connection failed – check internet, firewall, or SSL issues on your server.<br>";
    } elseif ($http_code == 400) {
        echo "<strong>Likely cause:</strong> Bad request format (JSON payload issue).<br>";
    }
    die();  // Stop script here for now
}