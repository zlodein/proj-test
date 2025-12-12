<?php
/**
 * Хелпер для работы с Яндекс.Картами на серверной стороне
 */

class YandexMapsHelper {
    private $apiKey;
    private $staticApiKey;
    
    public function __construct($apiKey = null, $staticApiKey = null) {
        $this->apiKey = $apiKey ?: '0d3f02e3-3a2a-426d-a3a1-9952dcb199b9';
        $this->staticApiKey = $staticApiKey ?: 'c3cb2b6f-af48-4850-95c6-7630afba5848';
    }

    
    /**
     * Получение URL статичной карты
     * 
     * @param float $lat Широта
     * @param float $lng Долгота
     * @param string $size Размер карты (например, "600,400")
     * @param int $zoom Уровень зума (1-17)
     * @param string $pinColor Цвет пина (pm2rdm - красный, pm2blm - синий, pm2gnm - зеленый)
     * @return string URL статичной карты
     */
    /**
    * Получение URL статичной карты (Static API v1 - новая версия)
    */
    public function getStaticMapUrl($lat, $lng, $size = '650,450', $zoom = 15, $pinColor = 'pmrdm') {
        // Формат метки: lng,lat,тип_метки
        // pmrdm - красная метка (pm=placemark, rd=red, m=marker)
        $marker = "{$lng},{$lat},{$pinColor}";
        
        // Параметры для Static API v1
        $params = [
            'll' => "{$lng},{$lat}",      // Центр карты: долгота,широта
            'size' => $size,               // Размер изображения
            'z' => $zoom,                  // Масштаб (0-17)
            'lang' => 'ru_RU',             // Язык
            'pt' => $marker,               // Метка на карте
            'apikey' => $this->staticApiKey // API-ключ
        ];
        
        return 'https://static-maps.yandex.ru/v1?' . http_build_query($params);
    }

    /**
    * Скачивание статичной карты
    */
    public function downloadStaticMap($lat, $lng, $savePath, $size = '650,450', $zoom = 15) {
        $url = $this->getStaticMapUrl($lat, $lng, $size, $zoom);
        
        try {
            // Создаем директорию, если ее нет
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            
            // Используем cURL
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_HTTPHEADER => [
                    'Accept: image/png,image/*',
                    'Accept-Language: ru-RU,ru;q=0.9',
                ]
            ]);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Логируем для отладки
            if ($httpCode !== 200) {
                error_log("Static Maps API v1 error: HTTP {$httpCode}");
                error_log("URL: {$url}");
                error_log("Error: {$error}");
                error_log("Response: " . substr($imageData, 0, 500));
                return false;
            }
            
            // Проверяем, что получили изображение
            if (!$imageData || (strpos($contentType, 'image') === false && $httpCode == 200)) {
                error_log("Invalid content type: {$contentType}");
                return false;
            }
            
            // Сохраняем файл
            $result = file_put_contents($savePath, $imageData);
            if ($result === false) {
                error_log("Failed to save map to: {$savePath}");
                return false;
            }
            
            error_log("Map saved successfully: {$savePath}, size: {$result} bytes");
            return true;
            
        } catch (Exception $e) {
            error_log("Exception downloading map: " . $e->getMessage());
            return false;
        }
    }

    
    /**
     * Геокодирование адреса (адрес -> координаты)
     * 
     * @param string $address Адрес для геокодирования
     * @return array|false Массив с ключами 'lat', 'lng', 'full_address' или false
     */
    public function geocodeAddress($address) {
        $url = sprintf(
            'https://geocode-maps.yandex.ru/1.x/?apikey=%s&geocode=%s&format=json',
            $this->apiKey,
            urlencode($address)
        );
        
        try {
            $response = @file_get_contents($url);
            
            if ($response === false) {
                return false;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['response']['GeoObjectCollection']['featureMember'][0])) {
                return false;
            }
            
            $geoObject = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'];
            $coords = explode(' ', $geoObject['Point']['pos']);
            
            return [
                'lat' => (float)$coords[1],
                'lng' => (float)$coords[0],
                'full_address' => $geoObject['metaDataProperty']['GeocoderMetaData']['text']
            ];
        } catch (Exception $e) {
            error_log("Ошибка геокодирования: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обратное геокодирование (координаты -> адрес)
     * 
     * @param float $lat
     * @param float $lng
     * @return string|false Адрес или false
     */
    public function reverseGeocode($lat, $lng) {
        $url = sprintf(
            'https://geocode-maps.yandex.ru/1.x/?apikey=%s&geocode=%s,%s&format=json',
            $this->apiKey,
            $lng,
            $lat
        );
        
        try {
            $response = @file_get_contents($url);
            
            if ($response === false) {
                return false;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['response']['GeoObjectCollection']['featureMember'][0])) {
                return false;
            }
            
            $geoObject = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'];
            return $geoObject['metaDataProperty']['GeocoderMetaData']['text'];
        } catch (Exception $e) {
            error_log("Ошибка обратного геокодирования: " . $e->getMessage());
            return false;
        }
    }
    
/**
 * Поиск ближайших станций метро
 * 
 * @param float $lat Широта
 * @param float $lng Долгота
 * @param int $limit Количество станций (по умолчанию 3)
 * @return array Массив станций с информацией
 */
public function findNearestMetro($lat, $lng, $limit = 3) {
    $url = sprintf(
        'https://geocode-maps.yandex.ru/1.x/?apikey=%s&geocode=%s,%s&kind=metro&results=%d&format=json',
        $this->apiKey,
        $lng,
        $lat,
        $limit
    );
    
    try {
        $response = @file_get_contents($url);
        if ($response === false) {
            error_log("Failed to fetch metro stations from: {$url}");
            return [];
        }
        
        $data = json_decode($response, true);
        $stations = [];
        
        if (isset($data['response']['GeoObjectCollection']['featureMember'])) {
            foreach ($data['response']['GeoObjectCollection']['featureMember'] as $member) {
                $geoObject = $member['GeoObject'];
                $coords = explode(' ', $geoObject['Point']['pos']);
                $stationLat = (float)$coords[1];
                $stationLng = (float)$coords[0];
                
                $distance = $this->calculateDistance($lat, $lng, $stationLat, $stationLng);
                
                // Извлекаем название станции (убираем "метро" из начала)
                $name = $geoObject['name'];
                $name = preg_replace('/^метро\s+/ui', '', $name);
                
                $stations[] = [
                    'name' => $name,
                    'description' => $geoObject['description'] ?? '',
                    'coords' => [$stationLat, $stationLng],
                    'distance' => $distance,
                    'distance_text' => $this->formatDistance($distance),
                    'walk_time' => round($distance / 83), // ~5 км/ч пешком = 83 м/мин
                    'walk_time_text' => $this->formatTravelTime(round($distance / 83)),
                    'drive_time' => round($distance / 500), // ~30 км/ч в городе = 500 м/мин
                    'drive_time_text' => $this->formatTravelTime(round($distance / 500))
                ];
            }
        }
        
        // Сортируем по расстоянию
        usort($stations, function($a, $b) {
            return $a['distance'] - $b['distance'];
        });
        
        return $stations;
    } catch (Exception $e) {
        error_log("Ошибка поиска метро: " . $e->getMessage());
        return [];
    }
}

/**
 * Расчёт расстояния между координатами (формула Гаверсина)
 * 
 * @param float $lat1 Широта точки 1
 * @param float $lng1 Долгота точки 1
 * @param float $lat2 Широта точки 2
 * @param float $lng2 Долгота точки 2
 * @return int Расстояние в метрах
 */
private function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371000; // Радиус Земли в метрах
    
    $φ1 = deg2rad($lat1);
    $φ2 = deg2rad($lat2);
    $Δφ = deg2rad($lat2 - $lat1);
    $Δλ = deg2rad($lng2 - $lng1);
    
    $a = sin($Δφ/2) * sin($Δφ/2) +
         cos($φ1) * cos($φ2) *
         sin($Δλ/2) * sin($Δλ/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return round($R * $c); // в метрах
}

/**
 * Форматирование расстояния
 * 
 * @param int $meters Расстояние в метрах
 * @return string Отформатированное расстояние
 */
public function formatDistance($meters) {
    if ($meters < 1000) {
        return $meters . ' м';
    }
    return round($meters / 1000, 1) . ' км';
}

/**
 * Форматирование времени в пути
 * 
 * @param int $minutes Время в минутах
 * @return string Отформатированное время
 */
public function formatTravelTime($minutes) {
    if ($minutes < 1) return 'менее минуты';
    if ($minutes == 1) return '1 минута';
    if ($minutes >= 2 && $minutes <= 4) return "{$minutes} минуты";
    if ($minutes >= 5 && $minutes <= 20) return "{$minutes} минут";
    
    $lastDigit = $minutes % 10;
    if ($lastDigit == 1 && $minutes != 11) return "{$minutes} минута";
    if ($lastDigit >= 2 && $lastDigit <= 4 && ($minutes < 10 || $minutes > 20)) return "{$minutes} минуты";
    
    return "{$minutes} минут";
}


    /**
     * Рендер HTML для интерактивной карты (режим редактора)
     * 
     * @param string $containerId ID контейнера для карты
     * @param string $inputId ID поля ввода адреса
     * @param array $coords Начальные координаты [lat, lng]
     * @param string $address Начальный адрес
     * @return string HTML-код
     */
    public function renderEditorMap($containerId, $inputId, $coords = [55.751574, 37.573856], $address = '') {
        ob_start();
        ?>
        <div class="location-block">
            <div class="location-map-container">
                <div id="<?php echo htmlspecialchars($containerId); ?>" class="yandex-map" style="width: 100%; height: 400px; border-radius: 8px;"></div>
            </div>
            <div class="location-info-container">
                <div class="form-group">
                    <label for="<?php echo htmlspecialchars($inputId); ?>">Местоположение</label>
                    <input 
                        type="text" 
                        id="<?php echo htmlspecialchars($inputId); ?>" 
                        class="form-control" 
                        placeholder="Введите адрес" 
                        value="<?php echo htmlspecialchars($address); ?>"
                        data-map-address
                    />
                    <small class="form-text text-muted">Начните вводить адрес для получения подсказок</small>
                </div>
                <input type="hidden" id="<?php echo $inputId; ?>_lat" value="<?php echo $coords[0]; ?>" />
                <input type="hidden" id="<?php echo $inputId; ?>_lng" value="<?php echo $coords[1]; ?>" />
            </div>
        </div>
        
        <script>
            (function() {
                const yandexMaps = new YandexMapsIntegration('<?php echo $this->apiKey; ?>', 'editor');
                const coords = [<?php echo $coords[0]; ?>, <?php echo $coords[1]; ?>];
                
                yandexMaps.initEditorMap('<?php echo $containerId; ?>', '<?php echo $inputId; ?>', coords);
                
                // Сохранение координат при обновлении
                document.addEventListener('yandexMapLocationUpdate', function(e) {
                    document.getElementById('<?php echo $inputId; ?>_lat').value = e.detail.lat;
                    document.getElementById('<?php echo $inputId; ?>_lng').value = e.detail.lng;
                });
            })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Рендер HTML для карты в режиме просмотра
     * 
     * @param string $containerId ID контейнера
     * @param array $coords Координаты [lat, lng]
     * @param string $address Адрес
     * @param bool $useStatic Использовать статичную карту
     * @return string HTML-код
     */
    public function renderViewMap($containerId, $coords, $address = '', $useStatic = false) {
        ob_start();
        ?>
        <div class="location-block">
            <?php if ($useStatic): ?>
                <div class="location-map-container">
                    <img 
                        src="<?php echo $this->getStaticMapUrl($coords[0], $coords[1]); ?>" 
                        alt="Карта местоположения" 
                        style="width: 100%; height: auto; border-radius: 8px;"
                    />
                </div>
            <?php else: ?>
                <div class="location-map-container">
                    <div id="<?php echo htmlspecialchars($containerId); ?>" class="yandex-map" style="width: 100%; height: 400px; border-radius: 8px;"></div>
                </div>
                <script>
                    (function() {
                        const yandexMaps = new YandexMapsIntegration('<?php echo $this->apiKey; ?>', 'view');
                        const coords = [<?php echo $coords[0]; ?>, <?php echo $coords[1]; ?>];
                        const address = '<?php echo addslashes($address); ?>';
                        
                        yandexMaps.initViewMap('<?php echo $containerId; ?>', coords, address);
                    })();
                </script>
            <?php endif; ?>
            
            <?php if ($address): ?>
            <div class="location-info-container">
                <p class="location-address"><strong>Адрес:</strong> <?php echo htmlspecialchars($address); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}



// Глобальная функция для быстрого доступа
function getYandexMapsHelper() {
    static $helper = null;
    if ($helper === null) {
        $helper = new YandexMapsHelper(
            '0d3f02e3-3a2a-426d-a3a1-9952dcb199b9',  // API ключ для JS Maps
            'c3cb2b6f-af48-4850-95c6-7630afba5848'   // API ключ для Static Maps
        );
    }
    return $helper;
}
