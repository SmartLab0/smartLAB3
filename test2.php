<?php
echo "محاولة الاتصال بـ google (بدون تحقق SSL):<br>";
$ch = curl_init('https://www.google.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 👈 هذا السطر الجديد
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 👈 وهذا

$response = curl_exec($ch);
$error = curl_error($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "❌ فشل: " . $error;
} else {
    echo "✅ نجح - كود: " . $http;
}
?>
