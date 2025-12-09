<?php
// api/generate-pdf.php - API endpoint для генерации PDF через PDFShift.io
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Получаем данные из POST запроса
$data = json_decode(file_get_contents('php://input'), true);
$hash = $data['hash'] ?? '';

if (empty($hash)) {
    http_response_code(400);
    echo json_encode(['error' => 'Hash parameter is required']);
    exit;
}

// Получаем презентацию по хэшу
$presentation = getPresentationByHash($hash);

if (!$presentation || $presentation['is_public'] != 1) {
    http_response_code(404);
    echo json_encode(['error' => 'Presentation not found or not public']);
    exit;
}

// Проверяем права на экспорт в PDF
$ownerId = $presentation['user_id'];
$canPrint = canUserPrint($ownerId);

if (!$canPrint) {
    http_response_code(403);
    echo json_encode([
        'error' => 'PDF export not available',
        'message' => 'Free plan does not include PDF export. Please upgrade your plan.'
    ]);
    exit;
}

// Конфигурация PDFShift.io
// ВАЖНО: Замените YOUR_API_KEY на ваш реальный API ключ
// Рекомендуется хранить ключ в отдельном конфигурационном файле
define('PDFSHIFT_API_KEY', 'YOUR_API_KEY'); // Замените на ваш ключ!

// URL презентации для конвертации
$presentationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
    . "://" . $_SERVER['HTTP_HOST'] . "/view.php?hash=" . $hash;

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
    'delay' => 2000, // Задержка для загрузки всех элементов
    'cookies' => [], // Можно передать куки для авторизации при необходимости
];

// Выполняем запрос к PDFShift API
$ch = curl_init('https://api.pdfshift.io/v3/convert/pdf');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pdfshiftData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode('api:' . PDFSHIFT_API_KEY)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Проверяем результат
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'error' => 'CURL error',
        'message' => $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'PDFShift API error',
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ]);
    exit;
}

// Сохраняем PDF во временную директорию
$tempDir = __DIR__ . '/../cache/pdf/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

$filename = 'presentation_' . $presentation['id'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
$filepath = $tempDir . $filename;

file_put_contents($filepath, $response);

// Логируем успешную генерацию
if (function_exists('logPdfGeneration')) {
    logPdfGeneration($presentation['id'], $ownerId, filesize($filepath));
}

// Возвращаем успешный ответ с URL файла
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'download_url' => '/api/download-pdf.php?file=' . urlencode($filename),
    'size' => filesize($filepath),
    'message' => 'PDF successfully generated'
]);
