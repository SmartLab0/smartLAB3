<?php
// setup.php - إنشاء قاعدة البيانات تلقائياً عند أول دخول

// إعدادات الاتصال
$servername = "localhost";
$username = "root";
$password = "";

try {
    // الاتصال بالسيرفر
    $conn = new mysqli($servername, $username, $password);
    
    // التحقق من الاتصال
    if ($conn->connect_error) {
        die("فشل الاتصال بالسيرفر: " . $conn->connect_error);
    }
    
    // قراءة ملف SQL
    $sql_commands = file_get_contents('database.sql');
    
    // تقسيم الأوامر (كل أمر بفاصلة منقوطة)
    $commands = explode(';', $sql_commands);
    
    // تنفيذ كل أمر
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command)) {
            if ($conn->query($command) === FALSE) {
                echo "خطأ في الأمر: " . $conn->error . "<br>";
            }
        }
    }
    
    echo "✅ تم إنشاء قاعدة البيانات والجداول بنجاح!<br>";
    echo "🎯 يمكنك الآن <a href='login.html'>تسجيل الدخول</a>";
    
    $conn->close();
    
} catch (Exception $e) {
    die("حدث خطأ: " . $e->getMessage());
}
?>