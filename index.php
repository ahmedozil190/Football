<?php
// إجبار السيرفر على العمل كبيئة PHP وتشغيل الصفحة الرئيسية
if (file_exists("index.html")) {
    include_once("index.html");
} else {
    echo json_encode(["status" => "Server is running"]);
}
?>
