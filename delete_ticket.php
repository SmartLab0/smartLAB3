<?php
session_start();
include 'config.php';

// تحقق من وجود المستخدم المسجل
if (!isset($_SESSION['user_id'])) {
    die("ليس لديك صلاحية");
}

// خذ رقم التذكرة من الرابط
if (isset($_GET['id'])) {
    $ticket_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // حذف التذكرة (فقط إذا كانت للمستخدم الحالي)
    $sql = "DELETE FROM tickets WHERE id = $ticket_id AND user_id = $user_id";
    
    if ($conn->query($sql)) {
        // نجح الحذف - ارجع لصفحة التذاكر
        header("Location: tickets.php");
        exit();
    } else {
        // فشل الحذف
        echo "خطأ في الحذف";
    }
} else {
    echo "لا يوجد رقم تذكرة";
}
?>