<?php
// PDFShift API Configuration
const PDFSHIFT_API_KEY = 'sk_e0c5392da71907de6f292079d650be00a0d55caf';
const PDFSHIFT_API_URL = 'https://api.pdfshift.io/v3/convert/html';

function getPDFShiftHeaders() {
    return [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PDFSHIFT_API_KEY . ':')
    ];
}

function convertToPDFWithPDFShift($htmlContent, $filename) {
    $payload = [
        'html' => $htmlContent,
        'landscape' => true,
        'options' => [
            'page_size' => 'A4',
            'page_width' => '297mm',
            'page_height' => '210mm',
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PDFSHIFT_API_URL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, getPDFShiftHeaders());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL Error: $error");
    }
    
    if ($httpCode !== 200) {
        $responseData = json_decode($response, true);
        error_log("PDFShift Error [$httpCode]: " . json_encode($responseData));
        throw new Exception("PDFShift API Error: " . ($responseData['message'] ?? 'Unknown error'));
    }
    
    return $response;
}

function handleExportRequest($action) {
    switch ($action) {
        case 'generate_presentation':
            handleGeneratePresentation();
            break;
            
        case 'export_pdf':
            handleExportPDF();
            break;
            
        default:
            jsonResponse(['error' => 'Неизвестное действие экспорта: ' . $action], 400);
    }
}

function handleGeneratePresentation() {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        die('Презентация не найдена');
    }
    
    // Проверяем доступ
    $presentation = getPresentationWithTheme($id);
    
    if (!$presentation) {
        die('Презентация не найдена');
    }
    
    // Проверяем, авторизован ли пользователь или есть публичный доступ
    $userId = getCurrentUserId();
    $isOwner = $userId && $presentation['user_id'] == $userId;
    $isPublic = $presentation['is_public'] == 1;
    
    if (!$isOwner && !$isPublic) {
        // Проверяем публичный доступ через hash
        $hash = $_GET['hash'] ?? '';
        if ($hash) {
            $publicPresentation = getPresentationByHash($hash);
            if (!$publicPresentation || $publicPresentation['id'] != $id) {
                die('Доступ запрещён');
            }
        } else {
            die('Доступ запрещён. Требуется авторизация или публичная ссылка.');
        }
    }
    
    $slides = $presentation['slides'];
    $title = htmlspecialchars($presentation['title']);
    $themeColor = $presentation['theme_color'] ?? '#2c7f8d';
    $showAllCurrencies = $presentation['show_all_currencies'] ?? false;
    
    // Для публичного просмотра всегда показываем валюты
    if ($isPublic) {
        $showAllCurrencies = true;
    }
    
    // Проверяем, может ли владелец печатать
    $ownerId = $presentation['user_id'];
    $canPrint = canUserPrint($ownerId);
    
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style media="print">
        <?php if (!$canPrint): ?>
        body * {
            display: none !important;
            visibility: hidden !important;
        }
        body:before {
            content: "Печать заблокирована. Владелец презентации использует бесплатный тариф. Для экспорта в PDF обновите тариф на presentation-realty.ru/tariffs.php";
            display: block !important;
            visibility: visible !important;
            text-align: center;
            padding: 100px 20px;
            font-size: 18px;
            color: #333;
        }
        <?php else: ?>
        @page {size: A4 landscape;margin: 0;}
        body {margin: 0 !important;padding: 0 !important;background: #fff !important;-webkit-print-color-adjust: exact !important;print-color-adjust: exact !important;color-adjust: exact !important;}
        .js-preview-page {margin: 0 !important;box-shadow: none !important;}
        .preview-content {padding: 0 !important;margin: 0 !important;}
        #pdf-controls, .no-print, button, .editor-btn {display: none !important;}
        <?php endif; ?>
    </style>
    <style>
        :root {--theme-main-color: <?php echo $themeColor; ?>;}
        .preview-content {padding: 20px;max-width: 1200px;margin: 0 auto;font-family: 'Roboto', sans-serif;}
        .js-preview-page {margin-bottom: 40px;box-shadow: 0 5px 20px rgba(0,0,0,0.1);border-radius: 10px;overflow: hidden;}
        .booklet-page {width: 100%;min-height: 700px;}
    </style>
</head>
<body>
    <div class="preview-content">
        <?php if (empty($slides)): ?>
            <div class="empty-slide" style="padding: 40px; text-align: center; color: #999;">
                <h3>Презентация пуста</h3>
            </div>
        <?php else: ?>
            <?php foreach ($slides as $index => $slide): ?>
                <?php if (!empty($slide['hidden'])) continue; ?>
                <div class="js-preview-page">
                    <div class="booklet-page">
                        <!-- Слайд будет отрендерен здесь -->
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
    <?php
    $html = ob_get_clean();
    
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function handleExportPDF() {
    // Требуем авторизацию
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'Требуется авторизация'], 401);
    }
    
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $userId = getCurrentUserId();
    
    if (!$id) {
        jsonResponse(['error' => 'ID презентации не указан'], 400);
    }
    
    // Проверяем доступ: пользователь должен быть владельцем
    if (!canAccessPresentation($id, $userId)) {
        jsonResponse(['error' => 'Доступ запрещён'], 403);
    }
    
    // Проверяем, может ли пользователь печатать по тарифу
    if (!canUserPrint($userId)) {
        jsonResponse([
            'error' => 'Экспорт недоступен',
            'message' => 'Ваш тариф не позволяет экспортировать в PDF. Обновите тариф.'
        ], 403);
    }
    
    $presentation = getPresentationWithTheme($id);
    
    if (!$presentation) {
        jsonResponse(['error' => 'Презентация не найдена'], 404);
    }
    
    try {
        // Получаем HTML презентации
        $htmlContent = generatePresentationHTML($presentation);
        
        // Конвертируем в PDF через PDFShift
        $pdfContent = convertToPDFWithPDFShift($htmlContent, $presentation['title']);
        
        // Возвращаем PDF файл
        $filename = 'presentation_' . $id . '_' . time() . '.pdf';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $pdfContent;
        exit;
        
    } catch (Exception $e) {
        error_log("PDF Export Error: " . $e->getMessage());
        jsonResponse([
            'error' => 'Ошибка при генерации PDF',
            'message' => APP_ENV === 'development' ? $e->getMessage() : 'Попробуйте позже'
        ], 500);
    }
}

function generatePresentationHTML($presentation) {
    $slides = $presentation['slides'];
    $title = htmlspecialchars($presentation['title']);
    $themeColor = $presentation['theme_color'] ?? '#2c7f8d';
    
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --theme-main-color: <?php echo $themeColor; ?>; }
        body { font-family: 'Roboto', sans-serif; background: #fff; }
        .preview-content { padding: 0; margin: 0; background: #fff; }
        .js-preview-page { 
            width: 297mm; 
            height: 210mm; 
            margin: 0; 
            padding: 0;
            page-break-after: always;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .js-preview-page:last-child { page-break-after: avoid; }
        .booklet-page { width: 100%; height: 100%; margin: 0; padding: 0; }
    </style>
</head>
<body>
    <div class="preview-content">
        <?php if (empty($slides)): ?>
            <div class="js-preview-page">
                <div style="text-align: center; color: #999;">Презентация пуста</div>
            </div>
        <?php else: ?>
            <?php foreach ($slides as $index => $slide): ?>
                <?php if (!empty($slide['hidden'])) continue; ?>
                <div class="js-preview-page">
                    <div class="booklet-page">
                        <?php renderSlideContent($slide, $index); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
    <?php
    return ob_get_clean();
}

function renderSlideContent($slide, $index) {
    switch ($slide['type']) {
        case 'cover':
            $amount = $slide['price_value'] ?? 0;
            $currency = $slide['currency'] ?? 'RUB';
            $dealType = $slide['deal_type'] ?? 'Аренда';
            $isRent = $dealType === 'Аренда';
            $symbol = CurrencyConverter::getSymbol($currency);
            $formattedAmount = $amount ? number_format($amount, 0, '.', ' ') : '';
            ?>
            <div style="width: 100%; height: 100%; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                <?php if (!empty($slide['background_image'])): ?>
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;">
                    <img src="<?php echo htmlspecialchars($slide['background_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <?php endif; ?>
                <div style="position: relative; z-index: 2; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 40px; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                    <div style="font-size: 48px; font-weight: bold;">
                        <?php echo !empty($slide['title']) ? $slide['title'] : 'ЭКСКЛЮЗИВНОЕ<br>ПРЕДЛОЖЕНИЕ'; ?>
                    </div>
                    <?php if ($amount > 0): ?>
                    <div style="font-size: 32px; font-weight: bold;">
                        <?php echo $formattedAmount . ' ' . $symbol . ($isRent ? ' / месяц' : ''); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            break;
            
        case 'image':
            ?>
            <div style="width: 100%; height: 100%;">
                <?php if (!empty($slide['image'])): ?>
                <img src="<?php echo htmlspecialchars($slide['image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php endif; ?>
            </div>
            <?php
            break;
            
        case 'gallery':
            ?>
            <div style="width: 100%; height: 100%; display: grid; grid-template-columns: repeat(3, 1fr); gap: 0;">
                <?php
                $images = $slide['images'] ?? [];
                for ($i = 0; $i < 3; $i++):
                    $img = $images[$i] ?? null;
                    $imgUrl = $img ? ($img['url'] ?? $img) : '';
                ?>
                    <div style="width: 100%; height: 100%;">
                        <?php if ($imgUrl): ?>
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <?php
            break;
            
        case 'grid':
            ?>
            <div style="width: 100%; height: 100%; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0;">
                <?php
                $images = $slide['images'] ?? [];
                for ($i = 0; $i < 4; $i++):
                    $img = $images[$i] ?? null;
                    $imgUrl = $img ? ($img['url'] ?? $img) : '';
                ?>
                    <div style="width: 100%; height: 100%;">
                        <?php if ($imgUrl): ?>
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <?php
            break;
            
        case 'description':
            ?>
            <div style="width: 100%; height: 100%; display: flex; padding: 40px; gap: 30px;">
                <div style="flex: 1;">
                    <h2 style="font-size: 28px; font-weight: bold; margin-bottom: 20px; color: var(--theme-main-color);">
                        <?php echo !empty($slide['title']) ? htmlspecialchars($slide['title']) : 'ОПИСАНИЕ'; ?>
                    </h2>
                    <div style="font-size: 14px; line-height: 1.6; color: #333;">
                        <?php echo !empty($slide['content']) ? nl2br(htmlspecialchars($slide['content'])) : 'Описание'; ?>
                    </div>
                </div>
                <?php
                $images = $slide['images'] ?? [];
                for ($i = 0; $i < 2 && $i < count($images); $i++):
                    $img = $images[$i] ?? null;
                    $imgUrl = $img ? ($img['url'] ?? $img) : '';
                ?>
                    <div style="flex: 0 0 25%; height: 100%;">
                        <?php if ($imgUrl): ?>
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <?php
            break;
            
        case 'characteristics':
            ?>
            <div style="width: 100%; height: 100%; display: flex; padding: 40px; gap: 30px;">
                <div style="flex: 0 0 40%; height: 100%;">
                    <?php if (!empty($slide['image'])): ?>
                    <img src="<?php echo htmlspecialchars($slide['image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <?php if (!empty($slide['title'])): ?>
                    <h2 style="font-size: 28px; font-weight: bold; margin-bottom: 20px; color: var(--theme-main-color);">
                        <?php echo htmlspecialchars($slide['title']); ?>
                    </h2>
                    <?php endif; ?>
                    <div style="flex: 1; overflow-y: auto;">
                        <?php
                        $items = $slide['items'] ?? [];
                        $displayItems = array_slice($items, 0, 12);
                        foreach ($displayItems as $item):
                        ?>
                            <div style="display: flex; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #eee;">
                                <div style="flex: 1; font-weight: 500; color: #666;">
                                    <?php echo htmlspecialchars($item['label'] ?? ''); ?>
                                </div>
                                <div style="flex: 1; color: #333;">
                                    <?php echo htmlspecialchars($item['value'] ?? ''); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php
            break;
            
        case 'contacts':
            ?>
            <div style="width: 100%; height: 100%; display: flex; padding: 40px; gap: 30px;">
                <div style="flex: 0 0 35%; display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <?php
                    $images = $slide['images'] ?? [];
                    for ($i = 0; $i < 2; $i++):
                        $img = $images[$i] ?? null;
                        $imgUrl = $img ? ($img['url'] ?? $img) : '';
                    ?>
                        <div style="width: 100%; aspect-ratio: 1;">
                            <?php if ($imgUrl): ?>
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    <h2 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; color: var(--theme-main-color);">
                        <?php echo !empty($slide['contact_title']) ? htmlspecialchars($slide['contact_title']) : 'Контакты'; ?>
                    </h2>
                    <div style="font-size: 20px; font-weight: bold; margin-bottom: 8px; color: #333;">
                        <?php echo !empty($slide['contact_name']) ? htmlspecialchars($slide['contact_name']) : 'Slide Estate'; ?>
                    </div>
                    <div style="font-size: 14px; color: #666; margin-bottom: 6px;">
                        <?php echo !empty($slide['contact_role']) ? htmlspecialchars($slide['contact_role']) : 'Онлайн-сервис'; ?>
                    </div>
                    <div style="font-size: 14px; color: var(--theme-main-color); font-weight: 500;">
                        <?php echo !empty($slide['contact_phone']) ? htmlspecialchars($slide['contact_phone']) : '+7 (900) 000-00-00'; ?>
                    </div>
                </div>
            </div>
            <?php
            break;
            
        default:
            ?>
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f5f5f5; color: #999;">
                <div>Тип слайда не поддерживается</div>
            </div>
            <?php
    }
}
?>
