<?php
// Get params from Pesapal redirect
$order_tracking_id = $_GET['OrderTrackingId'] ?? '';
$ticket_id = $_GET['OrderMerchantReference'] ?? $_GET['ref'] ?? 'Unknown'; // Fallback

// Pesapal setup (same as payment.php)
$base_url = 'https://cybqa.pesapal.com/pesapalv3';
$consumer_key    = 'TDpigBOOhs+zAl8cwH2Fl82jJGyD8xev';
$consumer_secret = '1KpqkfsMaihIcOlhnBo/gBZ5smw=';

// Get access token
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
    $status = 'Error: Could not authenticate with Pesapal.';
} else {
    // Query transaction status
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

    $status = $status_response['payment_status_description'] ?? 'Processing...';
}

// Fetch from Google Sheet (for details)
$script_url = 'https://script.google.com/macros/s/AKfycbxS7mbLcbOWLl1SfmUcpE2AJALi833ajeUNxtXVFnZITbR6N5vx4djQd9UYIwzNhY3q4Q/exec';
$fetch_url = $script_url . '?ticketId=' . urlencode($ticket_id);

$ch = curl_init($fetch_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// Default values
$names = 'Guest';
$ticket_type = 'Unknown';
$price = 'N/A';
$error = '';

if ($http_code === 200 && is_array($data) && !isset($data['error']) && !empty($data)) {
    $names = $data['fullName'] ?? 'Guest';
    $ticket_type = $data['ticketType'] ?? 'Unknown';
    $price = ($data['price'] ?? 0) . ' UGX';
    // Use API status over sheet (sheet may lag)
} else {
    $error = 'Ticket details not found yet. ';
}

if (strtoupper($status) !== 'COMPLETED') {
    $error .= 'Payment status: ' . $status . '. Please refresh in 10-20 seconds or contact support.';
} else {
    $error = ''; // Clear error on success
    // Optionally update sheet here if needed, but IPN handles it
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your NGA Annual Dinner Ticket - <?php echo htmlspecialchars($ticket_id); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; text-align: center; }
        .ticket { max-width: 600px; margin: 40px auto; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 4px solid #007bff; }
        h1 { color: #007bff; }
        .qr { margin: 30px 0; }
        .btn { padding: 12px 30px; background: #28a745; color: white; border: none; border-radius: 8px; font-size: 1.1em; cursor: pointer; margin: 10px; }
        .btn:hover { background: #218838; }
        .error { color: #e67e22; font-weight: bold; background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px; }
        .success { color: #155724; background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="ticket">
        <h1>🎉 NGA Annual Dinner Ticket</h1>
        
        <p><strong>Ticket ID:</strong> <?php echo htmlspecialchars($ticket_id); ?></p>
        <p><strong>Name(s):</strong> <?php echo htmlspecialchars($names); ?></p>
        <p><strong>Ticket Type:</strong> <?php echo htmlspecialchars($ticket_type); ?></p>
        <p><strong>Price:</strong> <?php echo htmlspecialchars($price); ?></p>
        <p><strong>Status:</strong> <span style="color:#28a745; font-weight:bold;"><?php echo htmlspecialchars($status); ?></span></p>
        <p><strong>Date:</strong> <?php echo date('d F Y'); ?></p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <p><button class="btn" onclick="location.reload()">🔄 Refresh Now</button></p>
        <?php else: ?>
            <div class="success">Your payment was successful! You can now download your ticket.</div>
            <div class="qr">
                <img src="https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=<?php echo urlencode($ticket_id); ?>" alt="QR Code">
                <p>Scan at the entrance</p>
            </div>
            <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <?php endif; ?>
    </div>
</body>
</html>