<?php
// pay.php - Safaricom Daraja STK Push (Sandbox)

$consumer_key = '1hZUqGn0D8uADyYShCJab9lIPrZbn1nLySqhGkklBTLcWlTa';
$consumer_secret = '2SQ5YfOq28rDF4Tot1xnaB3Hyi8ATKTXENUnCyEAs5RXTZIHJBMbwEZGHQa4tU3p';
$shortcode = '174379';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$phone = $_POST['phone'] ?? '';
$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0; // Get amount from form

if (substr($phone, 0, 1) === '0') {
    $phone = '254' . substr($phone, 1);
}
if (!preg_match('/^254[71]\d{8}$/', $phone) || $amount < 1) {
    die("<h3 style='color:red'>Invalid phone or amount</h3>");
}

$credentials = base64_encode("$consumer_key:$consumer_secret");
$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$token_response = curl_exec($ch);
curl_close($ch);
$token_data = json_decode($token_response);
$access_token = $token_data->access_token ?? die("<h2 style='color:red'>Error: Token failed</h2>");

$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

$stk_payload = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount, // Use the dynamic amount from the form
    'PartyA' => $phone,
    'PartyB' => $shortcode,
    'PhoneNumber' => $phone,
    'CallBackURL' => 'https://sandbox.safaricom.co.ke/mpesa/',
    'AccountReference' => 'Store-' . $amount . 'ksh',
    'TransactionDesc' => 'Payment for store items'
];

$ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stk_payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>HTTP Code: $http_code\nResponse: " . htmlspecialchars($response) . "</pre>";

if ($http_code === 200) {
    $data = json_decode($response);
    if (isset($data->ResponseCode) && $data->ResponseCode === '0') {
        echo "<h2 style='color:green'>STK Push sent to $phone!</h2>";
        echo "<p>Check your M-Pesa app. Amount: $amount KSH (sandbox).</p>";
        echo "<a href='index.html'>Back to Store</a>";
    } else {
        echo "<h2 style='color:red'>Failed</h2><pre>" . print_r($data, true) . "</pre>";
    }
} else {
    echo "<h2 style='color:red'>API Error ($http_code)</h2>";
}
?>