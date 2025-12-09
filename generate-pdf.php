<?php
// generate-pdf.php - Генерация PDF через PDFShift.io API
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_start();

// Получаем хэш из POST запроса
$hash = $_POST['hash'] ?? '';

if (empty($hash)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Хэш презентации не указан']);
    exit;
}

// Получаем презентацию по хэшу
$presentation = getPresentationByHash($hash);

if (!$presentation || $presentation['is_public'] != 1) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Презентация не найдена или не публична']);
    exit;
}

// Проверяем права владельца на экспорт в PDF
$ownerId = $presentation['user_id'];
$canPrint = canUserPrint($ownerId);

if (!$canPrint) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Экспорт в PDF недоступен на бесплатном тарифе. Обновите тариф.']);
    exit;
}

// ВАШ API КЛЮЧ ОТ PDFSHIFT.IO
// ВАЖНО: Замените на ваш реальный ключ!
define('PDFSHIFT_API_KEY', 'YOUR_API_KEY_HERE');

// Формируем URL презентации для конвертации
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$presentationUrl = $protocol . '://' . $host . '/view.php?hash=' . $hash . '&pdf=1';

// Параметры для PDFShift API
$pdfshiftData = [
    'source' => $presentationUrl,
    'landscape' => false,
    'use_print' => true,
    'format' => 'A4',
    'margin' => [
        'top' => '0mm',
        'bottom' => '0mm',
        'left' => '0mm',
        'right' => '0mm'
    ],
    'javascript' => true,
    'delay' => 3000, // Задержка 3 сек для загрузки всех элементов
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
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Увеличенный таймаут

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Проверяем ошибки CURL
if ($curlError) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ошибка соединения с PDFShift: ' . $curlError]);
    exit;
}

// Проверяем HTTP код ответа
if ($httpCode !== 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    
    // Пытаемся декодировать ответ от PDFShift
    $errorResponse = json_decode($response, true);
    $errorMessage = 'Ошибка генерации PDF';
    
    if ($errorResponse && isset($errorResponse['error'])) {
        $errorMessage = $errorResponse['error'];
    } elseif ($httpCode === 401) {
        $errorMessage = 'Неверный API ключ PDFShift. Проверьте настройки.';
    } elseif ($httpCode === 402) {
        $errorMessage = 'Превышен лимит конвертаций PDFShift. Обновите план.';
    }
    
    echo json_encode(['error' => $errorMessage, 'details' => $response]);
    exit;
}

// Успешно! Возвращаем PDF файл
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="presentation-' . $hash . '-' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($response));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $response;
exit;
