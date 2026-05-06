<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'غير مصرح']);
    exit;
}

if (!isset($_FILES['audio'])) {
    echo json_encode(['error' => 'لا يوجد ملف صوتي']);
    exit;
}

$api_key = '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';

$audio_file = $_FILES['audio']['tmp_name'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file' => new CURLFile($audio_file, 'audio/webm', 'audio.webm'),
    'model' => 'whisper-1',
    'language' => 'ar',
    'temperature' => 0,
    'response_format' => 'json',
    'prompt' => 'هذه محادثة أكاديمية عن الحاسوب والبرمجة والتجارب العلمية'
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['error' => 'فشل الاتصال: ' . $error]);
} else {
    echo $response;
}
?>