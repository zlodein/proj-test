<?php
function handleImageRequest($action) {
    global $method;
    
    switch ($action) {
        case 'upload_image':
            requireAuth();
            
            error_log("=== UPLOAD IMAGE START ===");
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $presentationId = filter_input(INPUT_POST, 'presentation_id', FILTER_VALIDATE_INT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                error_log("CSRF token verification failed");
                jsonResponse(['error' => 'Недействительный токен безопасности'], 403);
            }
            
            if (!$presentationId) {
                error_log("Invalid presentation ID");
                jsonResponse(['error' => 'Неверный ID презентации'], 400);
            }
            
            if (!canAccessPresentation($presentationId)) {
                error_log("Access denied to presentation");
                jsonResponse(['error' => 'Доступ к презентации запрещён'], 403);
            }
            
            if (!isset($_FILES['image'])) {
                error_log("No file in request");
                jsonResponse(['error' => 'Файл не передан в запросе'], 400);
            }
            
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер',
                    UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер',
                    UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
                    UPLOAD_ERR_NO_FILE => 'Файл не загружен',
                    UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
                    UPLOAD_ERR_CANT_WRITE => 'Ошибка записи файла',
                    UPLOAD_ERR_EXTENSION => 'PHP расширение остановило загрузку'
                ];
                
                $errorMsg = $errorMessages[$_FILES['image']['error']] ?? 'Неизвестная ошибка загрузки';
                jsonResponse(['error' => $errorMsg], 400);
            }
            
            $file = $_FILES['image'];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($mimeType, $allowedTypes)) {
                jsonResponse(['error' => 'Недопустимый формат файла. Разрешены: JPG, PNG, GIF, WEBP'], 400);
            }
            
            $maxSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                jsonResponse(['error' => 'Файл слишком большой. Максимум 10MB'], 400);
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (empty($extension)) {
                $extension = 'jpg';
            }
            
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $uploadDir = __DIR__ . '/../assets/uploads/';
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    jsonResponse(['error' => 'Не удалось создать папку для загрузок'], 500);
                }
            }
            
            if (!is_writable($uploadDir)) {
                jsonResponse(['error' => 'Папка загрузок недоступна для записи'], 500);
            }
            
            $filepath = $uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                jsonResponse(['error' => 'Ошибка сохранения файла на сервере'], 500);
            }
            
            try {
                $db = Database::getInstance()->getConnection();
                
                $stmt = $db->prepare("
                    INSERT INTO images (user_id, presentation_id, filename, original_name, mime_type, file_size, path, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    getCurrentUserId(),
                    $presentationId,
                    $filename,
                    $file['name'],
                    $mimeType,
                    $file['size'],
                    '/assets/uploads/' . $filename
                ]);
                
                if (!$result) {
                    throw new Exception("Failed to insert into database");
                }
                
                $imageId = $db->lastInsertId();
                
                jsonResponse([
                    'success' => true,
                    'image_id' => $imageId,
                    'filename' => $filename,
                    'url' => '/assets/uploads/' . $filename,
                    'original_name' => $file['name']
                ]);
                
            } catch (Exception $e) {
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
                jsonResponse(['error' => 'Ошибка сохранения в базу данных'], 500);
            }
            break;
            
        case 'upload_multiple':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $presentationId = filter_input(INPUT_POST, 'presentation_id', FILTER_VALIDATE_INT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$presentationId || !canAccessPresentation($presentationId)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            if (!isset($_FILES['images']) || empty($_FILES['images']['name'])) {
                jsonResponse(['error' => 'Файлы не выбраны'], 400);
            }
            
            $uploadedFiles = [];
            $errors = [];
            $fileCount = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
                
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Ошибка загрузки файла {$file['name']}";
                    continue;
                }
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $errors[] = "Недопустимый формат файла {$file['name']}";
                    continue;
                }
                
                if ($file['size'] > 10 * 1024 * 1024) {
                    $errors[] = "Файл {$file['name']} слишком большой";
                    continue;
                }
                
                try {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (empty($extension)) {
                        $extension = 'jpg';
                    }
                    
                    $filename = uniqid() . '_' . time() . '_' . $i . '.' . $extension;
                    $uploadDir = __DIR__ . '/../assets/uploads/';
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $filepath = $uploadDir . $filename;
                    
                    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                        throw new Exception('Ошибка сохранения файла');
                    }
                    
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("
                        INSERT INTO images (user_id, presentation_id, filename, original_name, mime_type, file_size, path, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        getCurrentUserId(),
                        $presentationId,
                        $filename,
                        $file['name'],
                        $mimeType,
                        $file['size'],
                        '/assets/uploads/' . $filename
                    ]);
                    
                    $imageId = $db->lastInsertId();
                    
                    $uploadedFiles[] = [
                        'id' => $imageId,
                        'filename' => $filename,
                        'url' => '/assets/uploads/' . $filename,
                        'original_name' => $file['name']
                    ];
                    
                } catch (Exception $e) {
                    if (isset($filepath) && file_exists($filepath)) {
                        @unlink($filepath);
                    }
                    $errors[] = "Ошибка загрузки {$file['name']}";
                }
            }
            
            jsonResponse([
                'success' => true,
                'uploaded' => $uploadedFiles,
                'errors' => $errors,
                'count' => count($uploadedFiles)
            ]);
            break;
            
        case 'delete_image':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $imageId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$imageId) {
                jsonResponse(['error' => 'Неверный ID изображения'], 400);
            }
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM images WHERE id = ? AND user_id = ?");
            $stmt->execute([$imageId, getCurrentUserId()]);
            $image = $stmt->fetch();
            
            if (!$image) {
                jsonResponse(['error' => 'Изображение не найдено'], 404);
            }
            
            $filepath = __DIR__ . '/../assets/uploads/' . $image['filename'];
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            
            $stmt = $db->prepare("UPDATE images SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$imageId]);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Изображение удалено'
                ]);
            } else {
                jsonResponse(['error' => 'Ошибка удаления из базы данных'], 500);
            }
            break;
            
        case 'get_images':
            requireAuth();
            
            $presentationId = filter_input(INPUT_GET, 'presentation_id', FILTER_VALIDATE_INT);
            
            if (!$presentationId || !canAccessPresentation($presentationId)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT id, filename, original_name, path, mime_type, file_size, created_at 
                FROM images 
                WHERE presentation_id = ? AND is_deleted = 0 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$presentationId]);
            $images = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'images' => $images,
                'count' => count($images)
            ]);
            break;
            
        default:
            jsonResponse(['error' => 'Неизвестное действие изображений: ' . $action], 400);
    }
}
?>