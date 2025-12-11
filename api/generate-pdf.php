<?php
// generate-pdf.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_start();

// Получаем ID презентации или хэш
$presentationId = $_POST['presentation_id'] ?? '';
$hash = $_POST['hash'] ?? '';

if (empty($presentationId) && empty($hash)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Не указан ID или хэш презентации']);
    exit;
}

// Получаем презентацию
if ($presentationId) {
    $presentation = getPresentation($presentationId);
} else {
    $presentation = getPresentationByHash($hash);
}

if (!$presentation) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Презентация не найдена']);
    exit;
}

// Проверяем права владельца на экспорт в PDF
$ownerId = $presentation['user_id'];
$canPrint = canUserPrint($ownerId);

if (!$canPrint) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Экспорт в PDF недоступен на бесплатном тарифе']);
    exit;
}

// ВАШ API КЛЮЧ ОТ PDFSHIFT.IO
define('PDFSHIFT_API_KEY', 'sk_e0c5392da71907de6f292079d650be00a0d55caf'); // ЗАМЕНИТЕ!

// Формируем URL для конвертации
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$presentationUrl = $protocol . '://' . $host . '/api.php?action=generate_presentation&id=' . $presentation['id'];

// Параметры для PDFShift API
$pdfshiftData = [
    'source' => $presentationUrl,
    'landscape' => true, // Альбомная ориентация для презентаций
    'use_print' => true,
    'format' => 'A4',
    'margin' => [
        'top' => '0mm',
        'bottom' => '0mm',
        'left' => '0mm',
        'right' => '0mm'
    ],
    'javascript' => true,
    'delay' => 3000,
    'image_quality' => 100,
    'print_background' => true
];

// Отправляем запрос к PDFShift API
$ch = curl_init('https://api.pdfshift.io/v3/convert/pdf');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pdfshiftData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode('api:' . PDFSHIFT_API_KEY)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ошибка соединения: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    $errorMessage = 'Ошибка генерации PDF';
    if ($httpCode === 401) {
        $errorMessage = 'Неверный API ключ PDFShift';
    } elseif ($httpCode === 402) {
        $errorMessage = 'Превышен лимит конвертаций PDFShift';
    }
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Возвращаем PDF файл
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="presentation-' . $presentation['id'] . '-' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($response));
echo $response;
exit;
