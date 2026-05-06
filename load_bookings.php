<?php
session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? 0;
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$response = ['bookings' => [], 'lectures' => []];

if ($user_id > 0 && isset($conn)) {
    // جلب الحجوزات
    $sql = "SELECT DATE(booking_date) as date, COUNT(*) as count 
            FROM bookings 
            WHERE user_id = ? 
            AND MONTH(booking_date) = ? 
            AND YEAR(booking_date) = ? 
            GROUP BY DATE(booking_date)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['bookings'][$row['date']] = $row['count'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>