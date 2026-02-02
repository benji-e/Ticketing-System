<?php
// process_payment.php - Saves to sheet via GET, then redirects to Pesapal

// 1. Collect & validate form data
$ticket_type = trim($_POST['ticket_type'] ?? '');
$price = (int)($_POST['price'] ?? 0);

if ($ticket_type === 'Single' || $ticket_type === 'Alumni') {
    $people = 1;
} elseif ($ticket_type === 'Couple') {
    $people = 2;
} elseif ($ticket_type === 'Table for 6') {
    $people = 6;
} else {
    die("Invalid ticket type");
}

$full_names = $phones = [];
for ($i = 1; $i <= $people; $i++) {
    $full_names[] = trim($_POST["full_name_$i"] ?? '');
    $phones[]     = trim($_POST["phone_$i"] ?? '');
}

$full_name_str = implode(', ', array_filter($full_names));
$phone_str     = implode(', ', array_filter($phones));

if (empty($full_name_str) || empty($phone_str)) {
    die("Missing name or phone details for one or more attendees");
}

// 2. Generate unique ticket ID
$ticket_id = 'NG0-NGA' . time() . '-' . rand(1000, 9999);

// 3. Pesapal config
$base_url        = 'https://cybqa.pesapal.com/pesapalv3';
$consumer_key    = 'TDpigBOOhs+zAl8cwH2Fl82jJGyD8xev';
$consumer_secret = '1KpqkfsMaihIcOlhnBo/gBZ5smw=';
$callback_url    = 'http://ngo-nga.ct.ws/ticket.php'; // change to https when SSL active

// 4. Get Pesapal token
$token_url  = "$base_url/api/Auth/RequestToken";
$token_data = json_encode(['consumer_key' => $consumer_key, 'consumer_secret' => $consumer_secret]);

$ch = curl_init($token_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $token_data,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token_result = json_decode($response, true);
if ($http_code !== 200 || empty($token_result['token'])) {
    die("Pesapal token failed (HTTP $http_code): " . ($token_result['message'] ?? $response));
}
$access_token = $token_result['token'];

// 5. IPN ID
$ipn_id = '9cc2ed4a-528b-4333-ba84-dad40a59876c';

// 6. Submit order to Pesapal
$order_url = "$base_url/api/Transactions/SubmitOrderRequest";
$order_data = [
    'id'              => $ticket_id,
    'currency'        => 'UGX',
    'amount'          => $price,
    'description'     => "$ticket_type Ticket for NGA Annual Dinner",
    'callback_url'    => $callback_url,
    'cancellation_url'=> $callback_url,
    'notification_id' => $ipn_id,
    'billing_address' => [
        'phone_number'  => $phones[0] ?? '',
        'email_address' => '',
        'country_code'  => 'UG',
        'first_name'    => explode(' ', $full_names[0])[0] ?? 'Customer',
        'last_name'     => explode(' ', $full_names[0])[1] ?? ''
    ]
];

$ch = curl_init($order_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($order_data),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$order_result = json_decode($response, true);
if ($http_code !== 200 || empty($order_result['redirect_url'])) {
    die("Pesapal order failed (HTTP $http_code): " . ($order_result['message'] ?? $response));
}

// === Record Pending in Google Sheet ===
$sheet_data = [
    'action'        => 'save',                    // ← this triggers the save branch
    'dateTime'      => date('Y-m-d H:i:s'),
    'fullName'      => $full_name_str,
    'phoneNumber'   => $phone_str,
    'ticketType'    => $ticket_type,
    'price'         => $price,
    'ticketId'      => $ticket_id,
    'paymentStatus' => 'Pending'
];

$script_url = 'https://script.google.com/macros/s/AKfycbxS7mbLcbOWLl1SfmUcpE2AJALi833ajeUNxtXVFnZITbR6N5vx4djQd9UYIwzNhY3q4Q/exec';
$fetch_url  = $script_url . '?' . http_build_query($sheet_data);

$ch = curl_init($fetch_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 30,
]);

$result    = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

// Debug logging
error_log("SAVE GET - Ticket: $ticket_id | HTTP: $http_code | Err: $curl_err | Resp: " . substr($result, 0, 500));

// Show error during testing (remove/comment later)
if ($http_code !== 200 || strpos($result, '"success"') === false) {
    echo "<pre style='background:#fff3cd; padding:25px; border:3px solid #e67e22; max-width:900px; margin:30px auto; font-family:monospace; white-space:pre-wrap;'>";
    echo "GOOGLE SHEETS SAVE FAILED\n\n";
    echo "Ticket ID     : $ticket_id\n";
    echo "HTTP Code     : $http_code\n";
    echo "cURL Error    : $curl_err\n";
    echo "Full URL sent : " . htmlspecialchars($fetch_url) . "\n\n";
    echo "Raw Response  :\n" . htmlspecialchars($result) . "\n";
    echo "</pre>";
    // exit;  // uncomment if you want to stop on failure
}

// === Redirect to Pesapal ===
header("Location: " . $order_result['redirect_url']);
exit;