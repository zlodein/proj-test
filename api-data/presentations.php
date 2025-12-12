<?php
// Подключаем хелпер для работы с Яндекс.Картами
require_once __DIR__ . '/../includes/yandex-maps-helper.php';

function handlePresentationRequest($action) {
    global $method;
    
    switch ($action) {
        case 'create_presentation':
            requireAuth();
            
            // Проверяем тариф перед созданием
            $tariffModel = new Tariff();
            if (!$tariffModel->canCreatePresentation(getCurrentUserId())) {
                jsonResponse(['success' => false, 'error' => 'Вы исчерпали лимит презентаций по текущему тарифу. Обновите тариф.'], 403);
            }
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (empty($title)) {
                jsonResponse(['error' => 'Укажите название презентации'], 400);
            }
            
            $presentationModel = new Presentation();
            
            // Все типы слайдов с изображениями-заглушками
            $defaultSlides = [
                // 1. Обложка
                [
                    'type' => 'cover',
                    'title' => 'ЭКСКЛЮЗИВНОЕ<br>ПРЕДЛОЖЕНИЕ',
                    'subtitle' => 'АБСОЛЮТНО НОВЫЙ ТАУНХАУС<br>НА ПЕРВОЙ ЛИНИИ',
                    'deal_type' => 'Аренда',
                    'currency' => 'RUB',
                    'price_value' => 1000000,
                    'price' => '1 000 000 ₽ / месяц',
                    'background_image' => '/assets/uploads/default-image/3-img.jpg',
                    'hidden' => false
                ],
                
                // 2. Изображение (одиночное)
                [
                    'type' => 'image',
                    'image' => '/assets/uploads/default-image/1-img.jpg',
                    'hidden' => false
                ],
                
                // 3. Галерея (3 изображения)
                [
                    'type' => 'gallery',
                    'images' => [
                        '/assets/uploads/default-image/2-img.jpg',
                        '/assets/uploads/default-image/4-img.jpg',
                        '/assets/uploads/default-image/5-img.jpg'
                    ],
                    'hidden' => false
                ],
                
                // 4. Характеристики
                [
                    'type' => 'characteristics',
                    'title' => 'ХАРАКТЕРИСТИКИ КВАРТИРЫ',
                    'image' => '/assets/uploads/default-image/6-img.jpg',
                    'items' => [
                        ['label' => 'Площадь квартиры:', 'value' => '350 кв.м.'],
                        ['label' => 'Количество комнат:', 'value' => '5'],
                        ['label' => 'Высота потолков:', 'value' => '3.2 м'],
                        ['label' => 'Ремонт:', 'value' => 'Евро'],
                        ['label' => 'Санузел:', 'value' => '2 раздельных'],
                        ['label' => 'Балкон:', 'value' => '2 застекленных'],
                        ['label' => 'Вид из окон:', 'value' => 'На парк'],
                        ['label' => 'Год постройки:', 'value' => '2023'],
                        ['label' => 'Парковка:', 'value' => 'Подземная'],
                        ['label' => 'Лифт:', 'value' => '2 пассажирских']
                    ],
                    'hidden' => false
                ],
                
                // 5. Сетка (4 изображения)
                [
                    'type' => 'grid',
                    'images' => [
                        '/assets/uploads/default-image/7-img.jpg',
                        '/assets/uploads/default-image/8-img.jpg',
                        '/assets/uploads/default-image/9-img.jpg',
                        '/assets/uploads/default-image/10-img.jpg'
                    ],
                    'hidden' => false
                ],
                
                // 6. Описание
                [
                    'type' => 'description',
                    'title' => 'ОПИСАНИЕ',
                    'content' => 'Эксклюзивный таунхаус на первой линии с видом на море. Современный дизайн, панорамное остекление, приватная терраса. Идеальное сочетание комфорта и престижа.',
                    'images' => [
                        '/assets/uploads/default-image/11-img.jpg',
                        '/assets/uploads/default-image/12-img.jpg'
                    ],
                    'hidden' => false
                ],
                
                // 7. Инфраструктура
                [
                    'type' => 'infrastructure',
                    'title' => 'ИНФРАСТРУКТУРА',
                    'content' => 'В шаговой доступности: пляж, рестораны, спа-центр, фитнес-клуб. Рядом парк и детские площадки. Отличная транспортная доступность.',
                    'images' => [
                        '/assets/uploads/default-image/13-img.jpg',
                        '/assets/uploads/default-image/14-img.jpg'
                    ],
                    'hidden' => false
                ],
                
                // 8. Особенности
                [
                    'type' => 'features',
                    'title' => 'ОСОБЕННОСТИ',
                    'items' => [
                        ['text' => 'Панорамные окна'],
                        ['text' => 'Система "умный дом"'],
                        ['text' => 'Теплый пол'],
                        ['text' => 'Видеонаблюдение'],
                        ['text' => 'Консьерж-сервис'],
                        ['text' => 'Собственный сад']
                    ],
                    'images' => [
                        '/assets/uploads/default-image/15-img.jpg',
                        '/assets/uploads/default-image/16-img.jpg'
                    ],
                    'avatar' => '',
                    'hidden' => false
                ],
                
// 9. Местоположение
[
    'type' => 'location',
    'title' => 'МЕСТОПОЛОЖЕНИЕ',
    'location_name' => 'ЖК "Успешная продажа"',
    'location_address' => 'Краснодарский край, Городской округ Сочи, ул. Морская, 15',
    'location_lat' => 43.585472,
    'location_lng' => 39.723098,
    'metro_stations' => [], // Добавляем поле для станций метро
    'hidden' => false
],

                
                // 10. Контакты
                [
                    'type' => 'contacts',
                    'contact_title' => 'Контакты',
                    'contact_name' => 'Slide Estate',
                    'contact_role' => 'Онлайн-сервис для риелторов',
                    'contact_phone' => '+7 (900) 000-00-00',
                    'contact_messengers' => 'Telegram | WhatsApp',
                    'images' => [
                        '/assets/uploads/default-image/17-img.jpg',
                        '/assets/uploads/default-image/18-img.jpg'
                    ],
                    'avatar' => '/assets/uploads/default-image/logo.jpg',
                    'hidden' => false
                ]
            ];
            
            $id = $presentationModel->create([
                'user_id' => getCurrentUserId(),
                'title' => $title,
                'description' => $description,
                'slides_data' => ['slides' => $defaultSlides],
                'show_all_currencies' => 0
            ]);
            
            if ($id) {
                jsonResponse([
                    'success' => true,
                    'id' => $id,
                    'message' => 'Презентация создана со всеми типами слайдов'
                ]);
            } else {
                jsonResponse(['error' => 'Ошибка создания презентации'], 500);
            }
            break;
            
        case 'get_presentations':
            requireAuth();
            
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50;
            $offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0;
            
            $presentationModel = new Presentation();
            $presentations = $presentationModel->getByUser(null, $limit, $offset);
            $total = $presentationModel->countByUser();
            
            jsonResponse([
                'success' => true,
                'presentations' => $presentations,
                'total' => $total
            ]);
            break;
            
        case 'get_presentation':
            requireAuth();
            
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$id) {
                jsonResponse(['error' => 'Неверный ID'], 400);
            }
            
            if (!canAccessPresentation($id)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $presentation = getPresentation($id);
            
            if (!$presentation) {
                jsonResponse(['error' => 'Презентация не найдена'], 404);
            }
            
            jsonResponse([
                'success' => true,
                'presentation' => $presentation
            ]);
            break;
            
        case 'update_presentation':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $title = trim($_POST['title'] ?? '');
            $slidesData = $_POST['slides_data'] ?? null;
            $csrfToken = $_POST['csrf_token'] ?? '';
            $themeColor = $_POST['theme_color'] ?? null;
            $coverImage = $_POST['cover_image'] ?? null;
            $status = $_POST['status'] ?? 'draft';
            $showAllCurrencies = filter_var($_POST['show_all_currencies'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$id) {
                jsonResponse(['error' => 'Неверный ID'], 400);
            }
            
            if (!canAccessPresentation($id)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $presentationModel = new Presentation();
            $updateData = [];
            
            if (!empty($title)) {
                $updateData['title'] = $title;
            }
            
            if ($themeColor && preg_match('/^#[0-9A-Fa-f]{6}$/', $themeColor)) {
                $updateData['theme_color'] = $themeColor;
            }
            
            if ($coverImage) {
                $updateData['cover_image'] = $coverImage;
            }
            
            $updateData['status'] = in_array($status, ['draft', 'published']) ? $status : 'draft';
            $updateData['last_autosave'] = date('Y-m-d H:i:s');
            $updateData['show_all_currencies'] = $showAllCurrencies ? 1 : 0;
            
            if ($slidesData) {
                $slides = json_decode($slidesData, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    jsonResponse(['error' => 'Неверный формат данных слайдов: ' . json_last_error_msg()], 400);
                }
                
                foreach ($slides as &$slide) {
                    // Ограничение характеристик до 12 строк
                    if ($slide['type'] === 'characteristics' && isset($slide['items'])) {
                        $slide['items'] = array_slice($slide['items'], 0, 12);
                    }
                    
                    // Ограничение особенностей до 9 строк
                    if ($slide['type'] === 'features' && isset($slide['items'])) {
                        $slide['items'] = array_slice($slide['items'], 0, 9);
                    }
                    
                    // Обработка слайда местоположения - сохраняем координаты
                    if ($slide['type'] === 'location') {
                        // Валидация и сохранение координат
                        if (isset($slide['location_lat'])) {
                            $slide['location_lat'] = floatval($slide['location_lat']);
                        }
                        if (isset($slide['location_lng'])) {
                            $slide['location_lng'] = floatval($slide['location_lng']);
                        }
                        // Убедимся, что адрес сохраняется
                        if (isset($slide['location_address'])) {
                            $slide['location_address'] = trim($slide['location_address']);
                        }
                    }
                    
                    // Гарантируем корректные изображения для галереи (3 изображения)
                    if ($slide['type'] === 'gallery' && isset($slide['images'])) {
                        $slide['images'] = array_slice($slide['images'], 0, 3);
                        // Заполняем пустые слоты заглушками
                        for ($i = count($slide['images']); $i < 3; $i++) {
                            $slide['images'][$i] = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                        }
                    }
                    
                    // Гарантируем корректные изображения для сетки (4 изображения)
                    if ($slide['type'] === 'grid' && isset($slide['images'])) {
                        $slide['images'] = array_slice($slide['images'], 0, 4);
                        // Заполняем пустые слоты заглушками
                        for ($i = count($slide['images']); $i < 4; $i++) {
                            $slide['images'][$i] = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
                        }
                    }
                    
                    // Гарантируем корректные изображения для описания (2 изображения)
                    if ($slide['type'] === 'description' && isset($slide['images'])) {
                        $slide['images'] = array_slice($slide['images'], 0, 2);
                        // Заполняем пустые слоты заглушками
                        for ($i = count($slide['images']); $i < 2; $i++) {
                            $slide['images'][$i] = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                        }
                    }
                    
                    // Гарантируем корректные изображения для инфраструктуры (2 изображения)
                    if ($slide['type'] === 'infrastructure' && isset($slide['images'])) {
                        $slide['images'] = array_slice($slide['images'], 0, 2);
                        // Заполняем пустые слоты заглушками
                        for ($i = count($slide['images']); $i < 2; $i++) {
                            $slide['images'][$i] = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                        }
                    }
                    
                    // Гарантируем корректные изображения для особенностей (2 изображения + аватар)
                    if ($slide['type'] === 'features' && isset($slide['images'])) {
                        $slide['images'] = array_slice($slide['images'], 0, 2);
                        // Заполняем пустые слоты заглушками
                        for ($i = count($slide['images']); $i < 2; $i++) {
                            $slide['images'][$i] = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                        }
                        
                        // Заглушка для аватара
                        if (empty($slide['avatar'])) {
                            $slide['avatar'] = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                        }
                    }
                    
                    // Гарантируем корректные изображения для контактов (2 изображения + аватар)
                    if ($slide['type'] === 'contacts' && isset($slide['images'])) {
                        $slide['images'] = array_slice($slide['images'], 0, 2);
                        // Заполняем пустые слоты заглушками
                        for ($i = count($slide['images']); $i < 2; $i++) {
                            $slide['images'][$i] = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                        }
                        
                        // Заглушка для аватара
                        if (empty($slide['avatar'])) {
                            $slide['avatar'] = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                        }
                    }
                }
                
                $updateData['slides_data'] = ['slides' => $slides];
            }
            
            if (empty($updateData)) {
                jsonResponse(['error' => 'Нет данных для обновления'], 400);
            }
            
            $result = $presentationModel->update($id, $updateData);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => $status === 'published' ? 'Презентация опубликована' : 'Изменения сохранены',
                    'status' => $status,
                    'cover_image' => $coverImage ?? null,
                    'theme_color' => $themeColor ?? '#2c7f8d',
                    'show_all_currencies' => $showAllCurrencies
                ]);
            } else {
                jsonResponse(['error' => 'Ошибка обновления презентации'], 500);
            }
            break;

case 'find_nearest_metro':
    requireAuth();
    
    if ($method !== 'POST') {
        jsonResponse(['error' => 'Метод не разрешён'], 405);
    }
    
    $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
    $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        jsonResponse(['error' => 'Недействительный токен'], 403);
    }
    
    if (!$lat || !$lng) {
        jsonResponse(['error' => 'Неверные координаты'], 400);
    }
    
    require_once __DIR__ . '/../includes/yandex-maps-helper.php';
    $yandexMaps = getYandexMapsHelper();
    
    $stations = $yandexMaps->findNearestMetro($lat, $lng, 3);
    
    jsonResponse([
        'success' => true,
        'stations' => $stations
    ]);
    break;


        case 'delete_presentation':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$id) {
                jsonResponse(['error' => 'Неверный ID'], 400);
            }
            
            if (!canAccessPresentation($id)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $presentationModel = new Presentation();
            $result = $presentationModel->delete($id);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Презентация удалена'
                ]);
            } else {
                jsonResponse(['error' => 'Ошибка удаления презентации'], 500);
            }
            break;
            
        case 'toggle_publish':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$id || !canAccessPresentation($id)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT status FROM presentations WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch();
            
            if (!$current) {
                jsonResponse(['error' => 'Презентация не найдена'], 404);
            }
            
            $newStatus = $current['status'] === 'published' ? 'draft' : 'published';
            
            $stmt = $db->prepare("UPDATE presentations SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$newStatus, $id]);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'status' => $newStatus,
                    'message' => $newStatus === 'published' ? 'Презентация опубликована' : 'Презентация снята с публикации'
                ]);
            } else {
                jsonResponse(['error' => 'Ошибка изменения статуса'], 500);
            }
            break;
            
        case 'duplicate_presentation':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$id || !canAccessPresentation($id)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $presentation = getPresentation($id);
            
            if (!$presentation) {
                jsonResponse(['error' => 'Презентация не найдена'], 404);
            }
            
            $presentationModel = new Presentation();
            $newId = $presentationModel->create([
                'user_id' => getCurrentUserId(),
                'title' => $presentation['title'] . ' (копия)',
                'description' => $presentation['description'],
                'slides_data' => json_encode(['slides' => $presentation['slides']]),
                'show_all_currencies' => $presentation['show_all_currencies'] ?? 0
            ]);
            
            if ($newId) {
                jsonResponse([
                    'success' => true,
                    'id' => $newId,
                    'message' => 'Презентация скопирована'
                ]);
            } else {
                jsonResponse(['error' => 'Ошибка копирования'], 500);
            }
            break;

        case 'upload_image':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['error' => 'Файл не загружен или произошла ошибка'], 400);
            }
            
            $presentationId = filter_input(INPUT_POST, 'presentation_id', FILTER_VALIDATE_INT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$presentationId || !canAccessPresentation($presentationId)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            // Проверяем тип файла
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                jsonResponse(['error' => 'Недопустимый тип файла. Разрешены только JPG, PNG, GIF, WebP'], 400);
            }
            
            // Проверяем размер файла (макс 10MB)
            if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                jsonResponse(['error' => 'Файл слишком большой. Максимальный размер 10MB'], 400);
            }
            
            // Создаем директорию для загрузок
            $uploadDir = __DIR__ . '/../assets/uploads/presentations/' . $presentationId . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Генерируем уникальное имя файла
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Перемещаем файл
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                // Создаем миниатюру если нужно
                if (function_exists('createThumbnail')) {
                    createThumbnail($filepath, $uploadDir . 'thumb_' . $filename, 300, 300);
                }
                
                // Возвращаем URL
                $url = '/assets/uploads/presentations/' . $presentationId . '/' . $filename;
                
                jsonResponse([
                    'success' => true,
                    'url' => $url,
                    'filename' => $filename
                ]);
            } else {
                jsonResponse(['error' => 'Ошибка при сохранении файла'], 500);
            }
            break;

        case 'generate_static_map':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $presentationId = filter_input(INPUT_POST, 'presentation_id', FILTER_VALIDATE_INT);
            $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
            $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$presentationId || !canAccessPresentation($presentationId)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            if (!$lat || !$lng) {
                jsonResponse(['error' => 'Необходимо указать координаты'], 400);
            }
            
            // Создаем директорию для кеша карт
            $cacheDir = __DIR__ . '/../cache/maps/';
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
            
            // Генерируем имя файла на основе координат
            $mapFilename = 'map_' . md5($lat . '_' . $lng) . '.png';
            $mapPath = $cacheDir . $mapFilename;
            
            // Проверяем, есть ли уже закешированная карта
            if (!file_exists($mapPath)) {
                $yandexMaps = getYandexMapsHelper();
                $result = $yandexMaps->downloadStaticMap($lat, $lng, $mapPath, '800,600', 15);
                
                if (!$result) {
                    jsonResponse(['error' => 'Ошибка загрузки карты'], 500);
                }
            }
            
            // Возвращаем URL карты
            $mapUrl = '/cache/maps/' . $mapFilename;
            
            jsonResponse([
                'success' => true,
                'map_url' => $mapUrl,
                'filename' => $mapFilename
            ]);
            break;

        default:
            jsonResponse(['error' => 'Неизвестное действие презентации: ' . $action], 400);
    }
}
?>
