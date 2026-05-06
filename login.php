<?php
session_start();

// الاتصال بقاعدة البيانات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'university_labs';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$conn->set_charset("utf8");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST['student_id'] ?? '');
    $password_input = $_POST['password'] ?? '';
    
    // 1. البحث عن المستخدم
    $sql = "SELECT * FROM users WHERE student_id = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $student_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 2. التحقق من كلمة المرور
        if (password_verify($password_input, $user['password'])) {
            // 3. حفظ في الجلسة
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['college'] = $user['college'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['specialization'] = $user['specialization'] ?? '';
            
            // 4. تحديث وقت الدخول
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            // 5. توجيه للصفحة الرئيسية
            header("Location: home.php");
            exit();
            
        } else {
            // كلمة مرور خاطئة
            header("Location: login.html?error=wrong_password");
            exit();
        }
    } else {
        // مستخدم غير موجود
        header("Location: login.html?error=not_found");
        exit();
    }
    
    $stmt->close();
} else {
    // إذا ما جاي من POST
    header("Location: login.html");
    exit();
}

$conn->close();
?>