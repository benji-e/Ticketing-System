<?php
// Read raw POST data from Pesapal (v3 sends JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    exit;
}

$order_tracking_id = $data['OrderTrackingId'] ?? '';
$merchant_ref = $data['OrderMerchantReference'] ?? ''; // Your ticket_id

if (empty($order_tracking_id) || empty($merchant_ref)) {
    http_response_code(400);
    exit;
}

// === Re-get token (or cache it) ===
$base_url = 'https://cybqa.pesapal.com/pesapalv3';
$consumer_key    = 'TDpigBOOhs+zAl8cwH2Fl82jJGyD8xev';
$consumer_secret = '1KpqkfsMaihIcOlhnBo/gBZ5smw=';

$token_url = "$base_url/api/Auth/RequestToken";
$token_data = json_encode(['consumer_key' => $consumer_key, 'consumer_secret' => $consumer_secret]);

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $token_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$token_response = json_decode(curl_exec($ch), true);
curl_close($ch);

$access_token = $token_response['token'] ?? '';
if (!$access_token) {
    http_response_code(500);
    exit;
}

// === Query Full Status ===
$status_url = "$base_url/api/Transactions/GetTransactionStatus?orderTrackingId=" . urlencode($order_tracking_id);

$ch = curl_init($status_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$status_response = json_decode(curl_exec($ch), true);
curl_close($ch);

$payment_status = $status_response['payment_status_description'] ?? 'UNKNOWN'; // e.g. COMPLETED, FAILED, PENDING

// === Update Google Sheet (simple: append new row with updated status) ===
$update_data = [
    'dateTime'      => date('Y-m-d H:i:s'),
    'fullName'      => 'UPDATE FOR ' . $merchant_ref, // Or fetch original if you store more
    'phoneNumber'   => '',
    'ticketType'    => '',
    'price'         => 0,
    'ticketId'      => $merchant_ref,
    'paymentStatus' => $payment_status
];

$google_script_url = 'https://script.google.com/macros/s/AKfycbxS7mbLcbOWLl1SfmUcpE2AJALi833ajeUNxtXVFnZITbR6N5vx4djQd9UYIwzNhY3q4Q/exec';
$ch = curl_init($google_script_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($update_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_exec($ch);
curl_close($ch);

// Always respond 200 OK to Pesapal
http_response_code(200);
echo json_encode(['status' => 'received']);
?>