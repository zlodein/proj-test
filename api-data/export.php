<?php
define('PDFSHIFT_API_KEY', 'sk_e0c5392da71907de6f292079d650be00a0d55caf');
define('PDFSHIFT_API_URL', 'https://api.pdfshift.io/v3/convert/pdf');

function handleExportRequest($action) {
    switch ($action) {
        case 'generate_presentation':
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/main.css" />

    <style media="print">
        <?php if (!$canPrint): ?>
        /* ПОЛНАЯ БЛОКИРОВКА ПЕЧАТИ ДЛЯ БЕСПЛАТНОГО ТАРИФА */
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
        @page {size: A4 landscape; margin: 0;}
        body {margin: 0 !important; padding: 0 !important; background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important;}
        .booklet-page {width: 297mm !important; height: 210mm !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; border-radius: 0 !important; page-break-after: always !important; break-after: page !important; transform: none !important; overflow: hidden !important;}
        .booklet-page:last-child {page-break-after: auto !important;}
        .preview-content {padding: 0 !important; margin: 0 !important;}
        .js-preview-page {margin: 0 !important; box-shadow: none !important;}
        .booklet-main__img,
        .booklet-char__top-square,
        .booklet-char__bottom-square,
        .booklet-img__top-square,
        .booklet-img__bottom-square,
        .booklet-galery__top-square,
        .booklet-galery__bottom-square,
        .booklet-grid__top-square,
        .booklet-grid__bottom-square,
        .booklet-info__top-square,
        .booklet-info__bottom-square,
        .booklet-stroen__top-square,
        .booklet-stroen__bottom-square,
        .booklet-osobenn__bottom-square,
        .booklet-osobenn__top-square,
        .booklet-map__top-square,
        .booklet-map__bottom-square,
        .booklet-contacts__top-square,
        .booklet-contacts__bottom-square {-webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;}
        #pdf-controls,
        .no-print,
        button,
        .editor-btn,
        .remove-row,
        .add-row,
        .add-feature-btn {display: none !important;}
        <?php endif; ?>
    </style>

    <style>
        :root {--theme-main-color: <?php echo $themeColor; ?>;}
        .booklet-main__img,
        .booklet-char__top-square,
        .booklet-char__bottom-square,
        .booklet-img__top-square,
        .booklet-img__bottom-square,
        .booklet-galery__top-square,
        .booklet-galery__bottom-square,
        .booklet-grid__top-square,
        .booklet-grid__bottom-square,
        .booklet-info__top-square,
        .booklet-info__bottom-square,
        .booklet-stroen__top-square,
        .booklet-stroen__bottom-square,
        .booklet-osobenn__bottom-square,
        .booklet-osobenn__top-square,
        .booklet-map__top-square,
        .booklet-map__bottom-square,
        .booklet-contacts__top-square,
        .booklet-contacts__bottom-square {background-color: <?php echo $themeColor; ?> !important;}   
        .preview-content {padding: 20px; max-width: 1200px; margin: 0 auto; font-family: 'Roboto', sans-serif;}
        .js-preview-page {margin-bottom: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;}
        .booklet-page {width: 100%; min-height: 700px;}
        .booklet-main__top,
        .booklet-main__center,
        .booklet-main__bottom,
        .booklet-char__title {display: block !important; opacity: 1 !important;}
        
        <?php if (!$canPrint): ?>
        /* Стили для отображения сообщения о блокировке печати */
        .print-disabled-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            color: white;
            z-index: 99999;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }
        
        .print-disabled-content {
            max-width: 600px;
            background: white;
            color: #333;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .print-disabled-content i {
            font-size: 64px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .print-disabled-content h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .print-disabled-content p {
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .upgrade-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin: 10px;
        }
        
        .upgrade-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        
        .cancel-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin: 10px;
        }
        
        .cancel-btn:hover {
            background: #5a6268;
        }
        
        /* Блокируем контекстное меню для печати */
        body.print-disabled {
            cursor: not-allowed;
        }
        <?php endif; ?>
    </style>
</head>
<body <?php if (!$canPrint) echo 'class="print-disabled"'; ?>>
    <?php if (!$canPrint): ?>
    <div class="print-disabled-overlay" id="printBlockOverlay">
        <div class="print-disabled-content">
            <i class="fas fa-ban"></i>
            <h2>Экспорт в PDF заблокирован</h2>
            <?php if ($isOwner): ?>
            <p>Ваш текущий тариф "Бесплатный" не позволяет экспортировать презентации в PDF.</p>
            <p>Обновите тариф, чтобы получить доступ к этой функции и многим другим возможностям.</p>
            <div>
                <a href="/tariffs.php" class="upgrade-btn" target="_blank">
                    <i class="fas fa-rocket"></i> Обновить тариф
                </a>
                <button class="cancel-btn" onclick="hidePrintBlock()">
                    <i class="fas fa-times"></i> Закрыть
                </button>
            </div>
            <?php else: ?>
            <p>Владелец этой презентации использует бесплатный тариф, который не позволяет экспортировать презентации в PDF.</p>
            <p>Для печати свяжитесь с владельцем презентации или создайте свою собственную.</p>
            <div>
                <a href="/" class="upgrade-btn" target="_blank">
                    <i class="fas fa-plus"></i> Создать свою презентацию
                </a>
                <button class="cancel-btn" onclick="hidePrintBlock()">
                    <i class="fas fa-times"></i> Закрыть
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="preview-content">
        <?php if (empty($slides)): ?>
            <div class="empty-slide" style="padding: 40px; text-align: center; color: #999; font-size: 18px; background: #f5f5f5; border-radius: 10px;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                <h3>Презентация пуста</h3>
                <p>Добавьте слайды в редакторе</p>
            </div>
        <?php else: ?>
            <?php foreach ($slides as $index => $slide): ?>
                <?php if (!empty($slide['hidden'])) continue; ?>
                
                <?php if ($slide['type'] === 'cover'): ?>
                    <?php
                    $amount = $slide['price_value'] ?? 0;
                    $currency = $slide['currency'] ?? 'RUB';
                    $dealType = $slide['deal_type'] ?? 'Аренда';
                    $isRent = $dealType === 'Аренда';
                    $symbol = CurrencyConverter::getSymbol($currency);
                    $formattedAmount = $amount ? number_format($amount, 0, '.', ' ') : '';
                    
                    if ($showAllCurrencies && $amount > 0) {
                        $rates = CurrencyConverter::getRates();
                    }
                    ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-main">
                                <div class="booklet-main__wrap">
                                    <div class="booklet-main__img">
                                        <?php if (!empty($slide['background_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($slide['background_image']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="booklet-main__content">
                                        <div class="booklet-main__top">
                                            <?php echo !empty($slide['title']) ? $slide['title'] : 'ЭКСКЛЮЗИВНОЕ<br>ПРЕДЛОЖЕНИЕ'; ?>
                                        </div>
                                        <?php if (!empty($slide['subtitle'])): ?>
                                        <div class="booklet-main__center">
                                            <?php echo $slide['subtitle']; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="booklet-main__bottom">
                                            <?php if (!empty($slide['deal_type'])): ?>
                                                <div class="booklet-main__type">
                                                    <?php echo htmlspecialchars($slide['deal_type']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($amount > 0): ?>
                                                <div class="booklet-main__price">
                                                    <?php echo $formattedAmount . ' ' . $symbol . ($isRent ? ' / месяц' : ''); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($showAllCurrencies && $amount > 0 && !empty($rates)): ?>
                                            <div class="all-currencies">
                                                <?php foreach ($rates as $currCode => $rate): ?>
                                                    <?php if ($currCode === $currency) continue; ?>
                                                    <?php
                                                    $converted = $amount * ($rates[$currency] / $rate);
                                                    $convertedFormatted = number_format($converted, 0, '.', ' ');
                                                    $currSymbol = CurrencyConverter::getSymbol($currCode);
                                                    ?>
                                                    <div class="currency-item">
                                                        <span class="currency-value"><?php echo $convertedFormatted; ?></span>
                                                        <span class="currency-symbol"><?php echo $currSymbol; ?></span>
                                                        <?php if ($isRent): ?><span style="color: #999; margin-left: 3px;">/ мес.</span><?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($slide['type'] === 'image'): ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-img">
                                <div class="booklet-img__top-square"></div>
                                <div class="booklet-img__bottom-square"></div>
                                <div class="booklet-img__wrap">
                                    <div class="booklet-img__img">
                                        <?php if (!empty($slide['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($slide['image']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($slide['type'] === 'gallery'): ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-galery">
                                <div class="booklet-galery__top-square"></div>
                                <div class="booklet-galery__bottom-square"></div>
                                <div class="booklet-galery__wrap">
                                    <?php
                                    $images = $slide['images'] ?? [];
                                    for ($i = 0; $i < 3; $i++):
                                        $img = $images[$i] ?? null;
                                        $imgUrl = $img ? ($img['url'] ?? $img) : '';
                                    ?>
                                        <div class="booklet-galery__img">
                                            <?php if ($imgUrl): ?>
                                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($slide['type'] === 'characteristics'): ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-char">
                                <div class="booklet-char__wrap">
                                    <?php if (!empty($slide['title'])): ?>
                                    <div class="booklet-char__title">
                                        <?php echo htmlspecialchars($slide['title']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="booklet-char__img">
                                        <?php if (!empty($slide['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($slide['image']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="booklet-char__content">
                                        <?php
                                        $items = $slide['items'] ?? [];
                                        $displayItems = array_slice($items, 0, 12);
                                        $squareHeight = count($displayItems) * 36;
                                        if ($squareHeight < 100) $squareHeight = 100;
                                        ?>
                                        <div class="booklet-char__top-square" style="height: <?php echo $squareHeight; ?>px;"></div>
                                        <div class="booklet-char__bottom-square"></div>
                                        <div class="booklet-char__table">
                                            <?php foreach ($displayItems as $item): ?>
                                                <div class="booklet-char__row">
                                                    <div class="booklet-char__item">
                                                        <?php echo htmlspecialchars($item['label'] ?? ''); ?>
                                                    </div>
                                                    <div class="booklet-char__item">
                                                        <?php echo htmlspecialchars($item['value'] ?? ''); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($slide['type'] === 'grid'): ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-grid">
                                <div class="booklet-grid__top-square"></div>
                                <div class="booklet-grid__bottom-square"></div>
                                <div class="booklet-grid__wrap">
                                    <?php
                                    $images = $slide['images'] ?? [];
                                    for ($i = 0; $i < 4; $i++):
                                        $img = $images[$i] ?? null;
                                        $imgUrl = $img ? ($img['url'] ?? $img) : '';
                                    ?>
                                        <div class="booklet-grid__img">
                                            <?php if ($imgUrl): ?>
                                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($slide['type'] === 'description'): ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-info">
                                <div class="booklet-info__top-square"></div>
                                <div class="booklet-info__bottom-square"></div>
                                <div class="booklet-info__wrap">
                                    <div class="booklet-info__block booklet-info__content">
                                        <div class="booklet-info__title">
                                            <?php echo !empty($slide['title']) ? htmlspecialchars($slide['title']) : 'ОПИСАНИЕ'; ?>
                                        </div>
                                        <div class="booklet-info__text">
                                            <?php echo !empty($slide['content']) ? nl2br(htmlspecialchars($slide['content'])) : 'Подробно опишите о своем объекте'; ?>
                                        </div>
                                    </div>
                                    <?php
                                    $images = $slide['images'] ?? [];
                                    for ($i = 0; $i < 2; $i++):
                                        $img = $images[$i] ?? null;
                                        $imgUrl = $img ? ($img['url'] ?? $img) : '';
                                    ?>
                                        <div class="booklet-info__block booklet-info__img">
                                            <?php if ($imgUrl): ?>
                                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($slide['type'] === 'infrastructure'): ?>
                        <div class="booklet-page page-cover js-preview-page">
                            <div class="booklet-page__inner">
                                <div class="booklet-content booklet-stroen">
                                    <div class="booklet-stroen__top-square"></div>
                                    <div class="booklet-stroen__bottom-square"></div>
                                    <div class="booklet-stroen__wrap">
                                        <div class="booklet-stroen__block booklet-stroen__content">
                                            <div class="booklet-stroen__title">
                                                <?php echo !empty($slide['title']) ? htmlspecialchars($slide['title']) : 'ИНФРАСТРУКТУРА'; ?>
                                            </div>
                                            <div class="booklet-stroen__text">
                                                <?php echo !empty($slide['content']) ? nl2br(htmlspecialchars($slide['content'])) : 'Подробно опишите инфраструктуру'; ?>
                                            </div>
                                        </div>
                                        <div class="booklet-stroen__grid">
                                            <?php
                                            $images = $slide['images'] ?? [];
                                            $img1 = $images[0] ?? null;
                                            $img1Url = $img1 ? ($img1['url'] ?? $img1) : '';
                                            $img2 = $images[1] ?? null;
                                            $img2Url = $img2 ? ($img2['url'] ?? $img2) : '';
                                            ?>
                                            <div class="booklet-stroen__block booklet-stroen__img">
                                                <?php if ($img1Url): ?>
                                                    <img src="<?php echo htmlspecialchars($img1Url); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="booklet-stroen__block booklet-stroen__img">
                                                <?php if ($img2Url): ?>
                                                    <img src="<?php echo htmlspecialchars($img2Url); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                <?php elseif ($slide['type'] === 'features'): ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-osobenn">
                                <div class="booklet-osobenn__wrap">
                                    <div class="booklet-osobenn__left">
                                        <div class="booklet-osobenn__title">
                                            <?php echo !empty($slide['title']) ? htmlspecialchars($slide['title']) : 'ОСОБЕННОСТИ'; ?>
                                        </div>
                                        <div class="booklet-osobenn__list">
                                            <?php
                                            $items = $slide['items'] ?? [];
                                            $displayItems = array_slice($items, 0, 9);
                                            foreach ($displayItems as $item):
                                            ?>
                                                <div class="booklet-osobenn__item">
                                                    <div class="booklet-osobenn__text">
                                                        <?php echo htmlspecialchars($item['text'] ?? ''); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="booklet-osobenn__bottom-square"></div>
                                    </div>
                                    <div class="booklet-osobenn__right">
                                        <div class="booklet-osobenn__top-square"></div>
                                        <?php
                                        $images = $slide['images'] ?? [];
                                        for ($i = 0; $i < 2; $i++):
                                            $img = $images[$i] ?? null;
                                            $imgUrl = $img ? ($img['url'] ?? $img) : '';
                                        ?>
                                            <div class="booklet-osobenn__img">
                                                <?php if ($imgUrl): ?>
                                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($slide['type'] === 'location'): ?>
                    <div class="booklet-page page-cover js-preview-page">
                        <div class="booklet-page__inner">
                            <div class="booklet-content booklet-map">
                                <div class="booklet-map__wrap">
                                    <div class="booklet-map__title">
                                        <?php echo !empty($slide['title']) ? htmlspecialchars($slide['title']) : 'МЕСТОПОЛОЖЕНИЕ'; ?>
                                    </div>
                                    <div class="booklet-map__img" id="map-<?php echo $index; ?>" style="background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999; min-height: 300px;">
                                        <div>Карта будет здесь</div>
                                    </div>
                                    <div class="booklet-map__content">
                                        <div class="booklet-map__top-square"></div>
                                        <div class="booklet-map__bottom-square"></div>
                                        <div class="booklet-map__info">
                                            <div class="booklet-map__name">
                                                <?php echo !empty($slide['location_name']) ? htmlspecialchars($slide['location_name']) : 'Объект'; ?>
                                            </div>
                                            <div class="booklet-map__text">
                                                <?php echo !empty($slide['location_address']) ? htmlspecialchars($slide['location_address']) : 'Адрес объекта'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($slide['type'] === 'contacts'): ?>
                        <div class="booklet-page page-cover js-preview-page">
                            <div class="booklet-page__inner">
                                <div class="booklet-content booklet-contacts">
                                    <div class="booklet-contacts__top-square"></div>
                                    <div class="booklet-contacts__bottom-square"></div>
                                    <div class="booklet-contacts__wrap">
                                        <div class="booklet-contacts-grid">
                                            <?php
                                            $images = $slide['images'] ?? [];
                                            for ($i = 0; $i < 2; $i++):
                                                $img = $images[$i] ?? null;
                                                $imgUrl = $img ? ($img['url'] ?? $img) : '';
                                            ?>
                                                <div class="booklet-contacts__block booklet-contacts__img">
                                                    <?php if ($imgUrl): ?>
                                                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php endif; ?>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="booklet-contacts__block booklet-contacts__content">
                                            <div class="booklet-contacts__header">
                                                <div class="booklet-contacts__title">
                                                    <?php echo !empty($slide['contact_title']) ? htmlspecialchars($slide['contact_title']) : 'Контакты'; ?>
                                                </div>
                                                <div class="booklet-contacts__avatar">
                                                    <div class="booklet-contacts__avatar-wrap">
                                                        <?php if (!empty($slide['avatar'])): ?>
                                                            <img src="<?php echo htmlspecialchars($slide['avatar']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="booklet-contacts__info">
                                                <div class="booklet-contacts__name">
                                                    <?php echo !empty($slide['contact_name']) ? htmlspecialchars($slide['contact_name']) : 'Контакт'; ?>
                                                </div>
                                                <div class="booklet-contacts__text">
                                                    <?php echo !empty($slide['contact_role']) ? htmlspecialchars($slide['contact_role']) : 'Должность'; ?>
                                                </div>
                                                <div class="booklet-contacts__text">
                                                    <?php echo !empty($slide['contact_phone']) ? htmlspecialchars($slide['contact_phone']) : 'Телефон'; ?>
                                                </div>
                                                <div class="booklet-contacts__text">
                                                    <?php echo !empty($slide['contact_messengers']) ? htmlspecialchars($slide['contact_messengers']) : 'Мессенджеры'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!$canPrint): ?>
        <div class="print-disabled" style="padding: 40px; text-align: center; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 10px; margin: 40px 0;">
            <i class="fas fa-ban" style="font-size: 64px; color: #e74c3c; margin-bottom: 20px;"></i>
            <h3 style="color: #495057; margin-bottom: 15px;">Экспорт в PDF недоступен</h3>
            <?php if ($isOwner): ?>
            <p style="color: #6c757d; margin-bottom: 20px; max-width: 600px; margin-left: auto; margin-right: auto;">
                Ваш текущий тариф "Бесплатный" не позволяет экспортировать презентации в PDF.<br>
                Обновите тариф, чтобы получить доступ к этой функции и многим другим возможностям.
            </p>
            <a href="/tariffs.php" class="upgrade-btn" target="_blank">
                <i class="fas fa-rocket"></i> Обновить тариф
            </a>
            <?php else: ?>
            <p style="color: #6c757d; margin-bottom: 20px; max-width: 600px; margin-left: auto; margin-right: auto;">
                Владелец этой презентации использует бесплатный тариф, который не позволяет экспортировать презентации в PDF.<br>
                Для печати свяжитесь с владельцем презентации или создайте свою собственную.
            </p>
            <a href="/" class="upgrade-btn" target="_blank">
                <i class="fas fa-plus"></i> Создать свою презентацию
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div id="pdf-controls" style="text-align: center; margin: 40px 0;">
            <button onclick="exportToPDF()" style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; 
                border: none; 
                padding: 15px 30px; 
                border-radius: 50px; 
                box-shadow: 0 5px 20px rgba(0,0,0,0.3); 
                cursor: pointer; 
                font-weight: bold;
                font-size: 16px;
                display: inline-flex; 
                align-items: center; 
                gap: 10px;
                font-family: 'Roboto', sans-serif;
                transition: all 0.3s;
                margin: 10px;
            " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <i class="fas fa-file-pdf" style="font-size: 20px;"></i>
                <span>Сохранить в PDF</span>
            </button>
            
            <button onclick="window.print()" style="
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white; 
                border: none; 
                padding: 15px 30px; 
                border-radius: 50px; 
                box-shadow: 0 5px 20px rgba(0,0,0,0.3); 
                cursor: pointer; 
                font-weight: bold;
                font-size: 16px;
                display: inline-flex; 
                align-items: center; 
                gap: 10px;
                font-family: 'Roboto', sans-serif;
                transition: all 0.3s;
                margin: 10px;
            " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <i class="fas fa-print" style="font-size: 20px;"></i>
                <span>Печать</span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <script>
    <?php if (!$canPrint): ?>
    // ПОЛНАЯ БЛОКИРОВКА ЭКСПОРТА
    function blockExport() {
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                showExportBlock();
                return false;
            }
            if (e.key === 'F12') {
                e.preventDefault();
                return false;
            }
        });
        
        window.addEventListener('beforeprint', function(e) {
            e.preventDefault();
            showExportBlock();
            return false;
        });
        
        window.print = function() {
            showExportBlock();
            return false;
        };
    }
    
    function showExportBlock() {
        const overlay = document.getElementById('printBlockOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    function hidePrintBlock() {
        const overlay = document.getElementById('printBlockOverlay');
        if (overlay) {
            overlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        blockExport();
    });
    <?php else: ?>
    function exportToPDF() {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Подготовка...</span>';
        button.disabled = true;

        const htmlContent = document.documentElement.outerHTML;
        
        fetch('/api.php?action=export_pdf&id=<?php echo $id; ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                html: htmlContent,
                title: '<?php echo $title; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.download_url) {
                const link = document.createElement('a');
                link.href = data.download_url;
                link.download = '<?php echo $title; ?>.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                button.innerHTML = originalText;
                button.disabled = false;
            } else {
                alert('Ошибка при экспорте в PDF: ' + (data.error || 'неизвестная ошибка'));
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при подключении к серверу');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
    <?php endif; ?>
    </script>
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
            break;
        
        case 'export_pdf':
            requireAuth();
            
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$id || !canAccessPresentation($id)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $userId = getCurrentUserId();
            if (!canUserPrint($userId)) {
                jsonResponse(['error' => 'Ваш тариф не позволяет экспортировать в PDF. Обновите тариф.'], 403);
            }
            
            $presentation = getPresentation($id);
            
            if (!$presentation) {
                jsonResponse(['error' => 'Презентация не найдена'], 404);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $htmlContent = $input['html'] ?? '';
            $presentationTitle = $input['title'] ?? 'Презентация';
            
            if (empty($htmlContent)) {
                jsonResponse(['error' => 'HTML контент не найден'], 400);
            }
            
            $response = convertHtmlToPdfViaPDFShift($htmlContent, $presentationTitle, $id);
            
            if (isset($response['error'])) {
                jsonResponse(['error' => $response['error']], 500);
            }
            
            jsonResponse($response, 200);
            break;
            
        default:
            jsonResponse(['error' => 'Неизвестное действие экспорта: ' . $action], 400);
    }
}

function convertHtmlToPdfViaPDFShift($htmlContent, $title, $presentationId) {
    try {
        $params = [
            'source' => $htmlContent,
            'landscape' => true,
            'format' => 'A4',
            'margin' => [
                'top' => 0,
                'bottom' => 0,
                'left' => 0,
                'right' => 0
            ],
            'wait_time' => 3000,
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => PDFSHIFT_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_USERPWD => 'api:' . PDFSHIFT_API_KEY,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('PDFShift API Error: ' . $error);
            return ['error' => 'Ошибка подключения к сервису конвертации: ' . $error];
        }
        
        if ($httpCode !== 200) {
            error_log('PDFShift API Response: ' . $response . ' (HTTP ' . $httpCode . ')');
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error'] ?? $errorData['message'] ?? $response;
            return ['error' => 'Ошибка сервиса конвертации: ' . $errorMsg];
        }
        
        if ($httpCode === 200 && strlen($response) > 100) {
            $uploadsDir = __DIR__ . '/../assets/uploads/pdfs/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            $filename = sanitizeFilename($title) . '_' . $presentationId . '_' . time() . '.pdf';
            $filepath = $uploadsDir . $filename;
            
            if (file_put_contents($filepath, $response) !== false) {
                return [
                    'success' => true,
                    'download_url' => '/assets/uploads/pdfs/' . $filename,
                    'filename' => $filename
                ];
            } else {
                return ['error' => 'Не удалось сохранить PDF файл на сервер'];
            }
        }
        
        return ['error' => 'Неожиданный ответ от сервиса конвертации'];
        
    } catch (Exception $e) {
        error_log('PDFShift Exception: ' . $e->getMessage());
        return ['error' => 'Ошибка при обработке запроса: ' . $e->getMessage()];
    }
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9_-]/u', '_', $filename);
    $filename = substr($filename, 0, 50);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}
?>