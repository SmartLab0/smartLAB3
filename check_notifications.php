check_no<?php
session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? 0;
$response = ['hasNotifications' => false, 'count' => 0];

if ($user_id > 0 && isset($conn)) {
    $sql = "SELECT COUNT(*) as count FROM bookings 
            WHERE user_id = ? 
            AND booking_date = CURDATE() 
            AND start_time BETWEEN CURTIME() AND ADDTIME(CURTIME(), '00:30:00')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $response['count'] = $row['count'];
        $response['hasNotifications'] = $row['count'] > 0;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>tifications.php