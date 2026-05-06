<?php
// fix_tickets.php - تحديث التذاكر القديمة
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once 'config.php';

if (!$conn || $conn->connect_errno !== 0) {
    die("فشل الاتصال بقاعدة البيانات");
}

echo "<h3>جاري تحديث التذاكر...</h3>";

// تحديث التذاكر بناءً على تاريخ الحجز
$update_sql = "UPDATE tickets t
               JOIN bookings b ON t.booking_id = b.id
               SET t.ticket_type = 
                   CASE 
                       WHEN b.booking_date >= CURDATE() THEN 'current'
                       ELSE 'past'
                   END
               WHERE t.ticket_type IS NULL OR t.ticket_type != 
                   CASE 
                       WHEN b.booking_date >= CURDATE() THEN 'current'
                       ELSE 'past'
                   END";

if ($conn->query($update_sql)) {
    $affected_rows = $conn->affected_rows;
    echo "<p>✅ تم تحديث <strong>$affected_rows</strong> تذكرة</p>";
} else {
    echo "<p>❌ خطأ في التحديث: " . $conn->error . "</p>";
}

// عرض التذاكر بعد التحديث
echo "<h4>التذاكر بعد التحديث:</h4>";
$view_sql = "SELECT t.id, t.ticket_code, t.ticket_type, b.booking_date, 
                    CASE 
                        WHEN b.booking_date >= CURDATE() THEN 'مستقبل/حالي'
                        ELSE 'فائت'
                    END as date_status
             FROM tickets t
             JOIN bookings b ON t.booking_id = b.id
             WHERE t.user_id = ? 
             ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($view_sql);
$user_id = $_SESSION['user_id'];
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' style='width:100%; border-collapse:collapse;'>
        <tr>
            <th>كود التذكرة</th>
            <th>نوع التذكرة</th>
            <th>تاريخ الحجز</th>
            <th>الحالة</th>
        </tr>";

while ($row = $result->fetch_assoc()) {
    $type_ar = ($row['ticket_type'] == 'current') ? 'حالية' : 'فائتة';
    $type_class = ($row['ticket_type'] == 'current') ? 'style=\"color:green;\"' : 'style=\"color:red;\"';
    
    echo "<tr>
            <td>{$row['ticket_code']}</td>
            <td {$type_class}><strong>{$type_ar}</strong></td>
            <td>{$row['booking_date']}</td>
            <td>{$row['date_status']}</td>
          </tr>";
}

echo "</table>";

$stmt->close();
$conn->close();

echo "<br><a href='tickets.php'>↩ العودة إلى صفحة التذاكر</a>";
?>