<?php
// تفعيل كشف الأخطاء لمعرفة السبب الحقيقي للـ 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$url = $_GET['url'] ?? null;
if (!$url) die("URL Missing");

$userAgent = 'VLC/3.0.18 LibVLC/3.0.18';

// المحاولة الأولى: باستخدام cURL (الأفضل)
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (!curl_errno($ch)) {
        header("Content-Type: " . ($contentType ?: "application/octet-stream"));
        echo $response;
        curl_close($ch);
        exit;
    }
    curl_close($ch);
}

// المحاولة الثانية (الخطة البديلة): إذا فشل cURL أو لم يكن موجوداً
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: $userAgent\r\n" .
                    "Accept: */*\r\n",
        "follow_location" => 1,
        "timeout" => 30
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    // محاكاة نوع الملف
    header("Content-Type: application/octet-stream");
    echo $response;
} else {
    echo "Proxy Error: Unable to fetch stream. Server might be blocking outbound connections.";
}
?>
