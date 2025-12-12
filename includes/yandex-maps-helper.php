<?php
/**
 * Хелпер для работы с Яндекс.Картами на серверной стороне
 */

class YandexMapsHelper {
    private $apiKey;
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: '0d3f02e3-3a2a-426d-a3a1-9952dcb199b9';
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
    public function getStaticMapUrl($lat, $lng, $size = '600,400', $zoom = 15, $pinColor = 'pm2rdm') {
        $marker = "{$lng},{$lat},{$pinColor}";
        
        return sprintf(
            'https://static-maps.yandex.ru/1.x/?ll=%s,%s&size=%s&z=%d&l=map&pt=%s&apikey=%s',
            $lng,
            $lat,
            $size,
            $zoom,
            $marker,
            $this->apiKey
        );
    }
    
    /**
     * Скачивание статичной карты и сохранение ее локально (для PDF)
     * 
     * @param float $lat
     * @param float $lng
     * @param string $savePath Путь для сохранения
     * @param string $size
     * @param int $zoom
     * @return string|false Путь к сохраненному файлу или false при ошибке
     */
    public function downloadStaticMap($lat, $lng, $savePath, $size = '600,400', $zoom = 15) {
        $url = $this->getStaticMapUrl($lat, $lng, $size, $zoom);
        
        try {
            $imageData = @file_get_contents($url);
            
            if ($imageData === false) {
                error_log("Ошибка загрузки карты с URL: {$url}");
                return false;
            }
            
            // Создаем директорию, если ее нет
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (file_put_contents($savePath, $imageData) === false) {
                error_log("Ошибка сохранения карты: {$savePath}");
                return false;
            }
            
            return $savePath;
        } catch (Exception $e) {
            error_log("Исключение при загрузке карты: " . $e->getMessage());
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
        $helper = new YandexMapsHelper();
    }
    return $helper;
}
