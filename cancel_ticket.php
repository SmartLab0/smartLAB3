<?php
// cancel_ticket.php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("غير مصرح");
}

require_once 'config.php';

$ticket_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// تأكد أن التذكرة للطالب نفسه
$check_sql = "SELECT id FROM tickets WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // تحديث التذكرة
    $update_sql = "UPDATE tickets SET status = 'absent', ticket_type = 'past' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $ticket_id);
    
    if ($update_stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $update_stmt->close();
} else {
    echo "not_found";
}

$stmt->close();
$conn->close();
?>