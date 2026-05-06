<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['id'] ?? 0;

if ($booking_id > 0) {
    // التحقق أن الحجز للمستخدم الحالي
    $check_sql = "SELECT id FROM bookings WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // حذف التذكرة المرتبطة أولاً
        $delete_ticket_sql = "DELETE FROM tickets WHERE booking_id = ?";
        $stmt_ticket = $conn->prepare($delete_ticket_sql);
        $stmt_ticket->bind_param("i", $booking_id);
        $stmt_ticket->execute();
        $stmt_ticket->close();
        
        // ثم حذف الحجز
        $delete_sql = "DELETE FROM bookings WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $booking_id);
        
        if ($stmt->execute()) {
            header("Location: labs.php?cancel_success=1");
            exit();
        } else {
            header("Location: labs.php?cancel_error=1");
            exit();
        }
        $stmt->close();
    } else {
        header("Location: labs.php?cancel_error=1");
        exit();
    }
} else {
    header("Location: labs.php");
    exit();
}
?>