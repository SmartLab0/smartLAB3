<?php
// profile.php - بداية الملف
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// بيانات المستخدم من الجلسة
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['fullname'] ?? '';
$student_id = $_SESSION['student_id'] ?? '';
$college = $_SESSION['college'] ?? '';
$email = $_SESSION['email'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$specialization = $_SESSION['specialization'] ?? '';

// متغيرات لعرض التواريخ الحقيقية من قاعدة البيانات
$created_at_formatted = 'غير متوفر';
$last_login_formatted = 'غير متوفر';

// جلب التواريخ الحقيقية من قاعدة البيانات عند تحميل الصفحة لأول مرة
if ($conn && $conn->connect_errno === 0) {
    $user_info_sql = "SELECT created_at, last_login FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_info_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_info_result = $stmt->get_result();
    
    if ($user_info_row = $user_info_result->fetch_assoc()) {
        if (!empty($user_info_row['created_at']) && $user_info_row['created_at'] !== '0000-00-00 00:00:00') {
            $created_at_formatted = date("Y-m-d", strtotime($user_info_row['created_at']));
        }
        if (!empty($user_info_row['last_login']) && $user_info_row['last_login'] !== '0000-00-00 00:00:00') {
            $last_login_formatted = date("Y-m-d H:i", strtotime($user_info_row['last_login']));
        }
    }
    $stmt->close();
}

// معالجة حفظ البيانات (تم تحسين رسائل الخطأ)
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $new_fullname = trim($_POST['fullname'] ?? $full_name);
    $new_email = trim($_POST['email'] ?? $email);
    $new_phone = trim($_POST['phone'] ?? $phone);
    $new_college = trim($_POST['college'] ?? $college);
    $new_specialization = trim($_POST['specialization'] ?? $specialization);

    // تحديث قاعدة البيانات
    if ($conn && $conn->connect_errno === 0) {
        $update_sql = "UPDATE users SET 
                      fullname = ?, 
                      email = ?, 
                      phone = ?, 
                      college = ?, 
                      specialization = ? 
                      WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", 
            $new_fullname,
            $new_email,
            $new_phone,
            $new_college,
            $new_specialization,
            $user_id
        );
        
        if ($stmt->execute()) {
            // تحديث الجلسة بنجاح
            $_SESSION['fullname'] = $new_fullname;
            $_SESSION['email'] = $new_email;
            $_SESSION['phone'] = $new_phone;
            $_SESSION['college'] = $new_college;
            $_SESSION['specialization'] = $new_specialization;
            
            // تحديث المتغيرات لعرضها في النموذج
            $full_name = $new_fullname;
            $email = $new_email;
            $phone = $new_phone;
            $college = $new_college;
            $specialization = $new_specialization;
            
            $success_message = "تم تحديث البيانات بنجاح!";
        } else {
            $error_message = "خطأ في تحديث قاعدة البيانات: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "خطأ في الاتصال بقاعدة البيانات. لا يمكن حفظ التغييرات.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - منصة المعامل الذكية</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- الهيدر -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="logo.jpg" alt="شعار الجامعة" class="header-logo">
            </div>
            
            <div class="controls-section">
                <button class="icon-btn" onclick="window.location.href='home.php'">
                    <i class="fas fa-home"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="main-content" style="max-width: 800px; margin: 40px auto;">
        <div class="profile-container">
            <!-- عنوان الصفحة -->
            <div class="profile-header">
                <h1 class="profile-title">
                    <i class="far fa-user-circle"></i>
                    الملف الشخصي
                </h1>
                <p class="profile-subtitle">تعديل بياناتك الشخصية</p>
            </div>

            <!-- رسالة النجاح -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <!-- رسالة الخطأ -->
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- نموذج التعديل -->
            <form class="profile-form" method="POST" action="">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="far fa-id-card"></i>
                        المعلومات الأساسية
                    </h3>
                    
                    <div class="form-grid">
                        <!-- الاسم الكامل -->
                        <div class="input-group">
                            <label for="fullname">
                                <i class="far fa-user"></i>
                                الاسم الكامل
                            </label>
                            <input type="text" id="fullname" name="fullname" 
                                   value="<?php echo htmlspecialchars($full_name); ?>" 
                                   required>
                        </div>

                        <!-- الرقم الجامعي (غير قابل للتعديل) -->
                        <div class="input-group">
                            <label for="student_id">
                                <i class="far fa-id-card"></i>
                                الرقم الجامعي
                            </label>
                            <input type="text" id="student_id" name="student_id" 
                                   value="<?php echo htmlspecialchars($student_id); ?>" 
                                   readonly
                                   style="background-color: #f5f5f5;">
                            <small class="form-hint">غير قابل للتعديل</small>
                        </div>

                        <!-- البريد الإلكتروني -->
                        <div class="input-group">
                            <label for="email">
                                <i class="far fa-envelope"></i>
                                البريد الإلكتروني
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   required>
                        </div>

                        <!-- رقم الجوال -->
                        <div class="input-group">
                            <label for="phone">
                                <i class="far fa-phone"></i>
                                رقم الجوال
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone); ?>" 
                                   pattern="05[0-9]{8}"
                                   maxlength="10"
                                   required>
                        </div>

                        <!-- الكلية -->
                        <div class="input-group">
                            <label for="college">
                                <i class="far fa-graduation-cap"></i>
                                الكلية
                            </label>
                            <select id="college" name="college" required class="modern-select">
                                <option value="">اختر كليتك</option>
                                <option value="كلية الهندسة" <?php echo $college == 'كلية الهندسة' ? 'selected' : ''; ?>>كلية الهندسة</option>
                                <option value="كلية الهندسة وعلوم الحاسب" <?php echo $college == 'كلية الهندسة وعلوم الحاسب' ? 'selected' : ''; ?>>كلية الهندسة وعلوم الحاسب</option>
                                <option value="كلية الطب" <?php echo $college == 'كلية الطب' ? 'selected' : ''; ?>>كلية الطب</option>
                                <option value="كلية العلوم" <?php echo $college == 'كلية العلوم' ? 'selected' : ''; ?>>كلية العلوم</option>
                                <option value="كلية العلوم والدراسات الإنسانية" <?php echo $college == 'كلية العلوم والدراسات الإنسانية' ? 'selected' : ''; ?>>كلية العلوم والدراسات الإنسانية</option>
                            </select>
                        </div>

                        <!-- التخصص -->
                        <div class="input-group">
                            <label for="specialization">
                                <i class="far fa-book"></i>
                                التخصص
                            </label>
                            <input type="text" id="specialization" name="specialization" 
                                   value="<?php echo htmlspecialchars($specialization); ?>" 
                                   placeholder="مثال: علوم الحاسب">
                        </div>
                    </div>
                </div>

                <!-- أزرار الحفظ والإلغاء -->
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i>
                        حفظ التعديلات
                    </button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='home.php'">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </button>
                </div>
            </form>

            <!-- معلومات إضافية (تم إصلاح التواريخ) -->
            <div class="profile-info">
                <div class="info-card">
                    <i class="far fa-calendar-alt"></i>
                    <div>
                        <h4>تاريخ التسجيل</h4>
                        <p><?php echo htmlspecialchars($created_at_formatted); ?></p>
                    </div>
                </div>
                
                <div class="info-card">
                    <i class="far fa-clock"></i>
                    <div>
                        <h4>آخر تحديث</h4>
                        <p><?php echo htmlspecialchars($last_login_formatted); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- تذييل الصفحة -->
    <footer class="profile-footer">
        <p>© 2026 منصة المعامل الذكية - جامعة الأمير سطام بن عبدالعزيز</p>
    </footer>

    <style>
        /* تنسيقات خاصة بصفحة الملف الشخصي */
        .profile-container {
            background: white;
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow-md);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .profile-title {
            color: var(--dark-green);
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .profile-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 16px;
            font-family: 'Cairo', sans-serif;
        }
        
        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        
        .form-hint {
            display: block;
            margin-top: 5px;
            color: var(--text-secondary);
            font-size: 13px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-cancel {
            background: var(--medium-gray);
            color: var(--text-primary);
            border: none;
            padding: 15px 30px;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-save:hover,
        .btn-cancel:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 40px;
        }
        
        .info-card {
            background: var(--light-gray);
            padding: 20px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .info-card i {
            font-size: 24px;
            color: var(--primary-green);
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .profile-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>