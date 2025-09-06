<?php
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.infobip.com/sms/2/text/advanced");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);

$headers = [
    "Authorization: App 39c8ad49f9d1acf308b258d38cedd7c8-2754401d-e8d1-4485-8c85-b60bc5a1345a", // Replace with your real API key
    "Content-Type: application/json",
    "Accept: application/json"
];

$data = [
    "messages" => [
        [
            "destinations" => [
                ["to" => "639551078233"] // <-- Replace with your phone number (include country code, e.g. 63 for PH)
            ],
            "from" => "447491163443", // <-- Sender ID (may need to use what your provider allows)
            "text" => "Hello! This is a test SMS from PHP."
        ]
    ]
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode == 200) {
        echo "✅ Success: " . $response;
    } else {
        echo "❌ Failed. HTTP Status $httpCode\nResponse: $response";
    }
}

curl_close($ch);
?>
