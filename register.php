<?php
session_start();

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
    $fullname = trim($_POST['full_name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($fullname)) $errors[] = "الاسم الكامل مطلوب";
    if (!preg_match('/^[0-9]{9}$/', $student_id)) $errors[] = "الرقم الجامعي يجب أن يكون 9 أرقام فقط";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "بريد إلكتروني غير صحيح";
    if (!preg_match('/^05[0-9]{8}$/', $phone)) $errors[] = "رقم الجوال يجب أن يبدأ بـ 05 ويحتوي 10 أرقام";
    if (empty($college)) $errors[] = "الرجاء اختيار الكلية";
    if (strlen($password) < 6) $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    if ($password !== $confirm_password) $errors[] = "كلمتا المرور غير متطابقتين";
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $check_sql = "SELECT id FROM users WHERE student_id = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $student_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['student_id'] = $student_id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['college'] = $college;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['specialization'] = $specialization;
            
            header("Location: home.php");
            exit();
            
        } else {
            $insert_sql = "INSERT INTO users (fullname, student_id, email, phone, college, specialization, password) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sssssss", $fullname, $student_id, $email, $phone, $college, $specialization, $hashed_password);
            
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['student_id'] = $student_id;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['college'] = $college;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                $_SESSION['specialization'] = $specialization;
                
                header("Location: home.php");
                exit();
            } else {
                $errors[] = "حدث خطأ في حفظ البيانات";
            }
        }
        
        $stmt->close();
    }
    
    if (!empty($errors)) {
        include 'error_page.php'; // أو عرض الأخطاء مباشرة
    }
} else {
    header("Location: login.html");
    exit();
}

$conn->close();
?>