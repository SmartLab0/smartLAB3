<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reply' => 'غير مصرح']);
    exit;
}

require_once 'config.php';

if (!$conn || $conn->connect_error) {
    echo json_encode(['reply' => 'خطأ في الاتصال بقاعدة البيانات']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'الطالب';
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    echo json_encode(['reply' => 'الرسالة فارغة']);
    exit;
}

$api_key = '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';

$messages = [
    [
        'role' => 'system', 
        'content' => 'أنت ChatGPT، عبقري ذكي خارق. الطالب اسمه: ' . $user_name . '.
تعليمات أساسية:
- تفهم العامية السعودية بكل لهجاتها
- تحلل السؤال من جميع جوانبه قبل الرد
- الشرح يكون خطوة بخطوة (Step by Step)
- تقسم الشرح إلى نقاط واضحة أو أرقام
- تستخدم أمثلة عملية وواقعية
- تشرح كأنك أستاذ خبير ومتفهم
- تكون ودود وكأنك صديق يشرح لصديقه

**مهم جداً:** بعد كل شرح، اسأل الطالب سؤال متابعة ذكي. مثلاً:
- "هل تريد أمثلة إضافية على هذا الموضوع؟"
- "هل تريد توضيح نقطة معينة؟"
- "هل تريد شرح جزء آخر من هذا الموضوع؟"
- "هل تريد تطبيق عملي على هذا المفهوم؟"

الهدف إن المحادثة تستمر وما تنتهي عند جواب واحد.'

    ]
];

// جلب آخر 10 محادثات للمستخدم من قاعدة البيانات
$history_sql = "SELECT user_message, bot_response FROM chatbot_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history_result = $stmt->get_result();

$history = [];
while ($row = $history_result->fetch_assoc()) {
    // نضيفها بالترتيب الصحيح (الأقدم للأحدث)
    array_unshift($history, ['role' => 'user', 'content' => $row['user_message']]);
    array_unshift($history, ['role' => 'assistant', 'content' => $row['bot_response']]);
}

// إضافة التاريخ للمessages
foreach ($history as $msg) {
    $messages[] = $msg;
}

// إضافة الرسالة الحالية
$messages[] = ['role' => 'user', 'content' => $message];

$data = [
    'model' => 'gpt-4o',
    'messages' => $messages,
    'max_tokens' => 2500,
    'temperature' => 0.9,
    'top_p' => 0.95
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['reply' => 'عذراً، حدث خطأ: ' . $error]);
} else {
    $result = json_decode($response, true);
    $reply = $result['choices'][0]['message']['content'] ?? 'لم أفهم سؤالك';
    
    // حفظ المحادثة في قاعدة البيانات
    $insert_sql = "INSERT INTO chatbot_logs (user_id, user_message, bot_response) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iss", $user_id, $message, $reply);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    echo json_encode(['reply' => $reply]);
}

$conn->close();
?>