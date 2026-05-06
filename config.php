<?php
// config.php - ملف الإعدادات الأساسية
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'university_labs';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    // لا نوقف البرنامج تماماً، بل نواصل مع رسالة خطأ
    $conn = null;
}

// تعيين الترميز
if ($conn) {
    $conn->set_charset("utf8");
}
?>