<?php
// إعدادات CORS للسماح للمتصفح بالوصول للبيانات
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$url = $_GET['url'] ?? null;
if (!$url) {
    die("خطأ: المعامل URL مطلوب");
}

// تنظيف الرابط والتأكد من صحته
$url = trim($url);
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    die("خطأ: رابط غير صالح");
}

$ch = curl_init();

// إعدادات الـ cURL لتحاكي كود الـ Node.js تماماً
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // اتباع إعادة التوجيه (301, 302)
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);         // أقصى عدد لعمليات التوجيه
curl_setopt($ch, CURLOPT_ENCODING, "");          // التعامل مع الضغط
curl_setopt($ch, CURLOPT_USERAGENT, 'VLC/3.0.18 LibVLC/3.0.18'); // تزييف هوية VLC
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);   // وقت محاولة الاتصال
curl_setopt($ch, CURLOPT_TIMEOUT, 30);          // وقت تحميل البيانات
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // تخطي فحص شهادة SSL للصلاحية
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// محاكاة بقية الـ Headers من كود Node.js
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: */*',
    'Connection: keep-alive'
]);

// تنفيذ الطلب
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    header("HTTP/1.1 500 Internal Server Error");
    echo "خطأ في السيرفر الوسيط: " . $error_msg;
} else {
    // تمرير نوع الملف والبيانات للمتصفح
    header("Content-Type: " . ($contentType ?: "application/octet-stream"));
    header("Cache-Control: no-cache");
    echo $response;
}

curl_close($ch);
?>
