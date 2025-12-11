<?php
// tariffs.php - Страница с тарифами
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/src/Models/',
        __DIR__ . '/src/Controllers/'
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

session_start();

$title = 'Тарифы';
$user = getCurrentUser();

// Проверяем, существует ли класс Tariff
if (!class_exists('Tariff')) {
    die('Класс Tariff не найден. Проверьте путь: ' . __DIR__ . '/src/Models/Tariff.php');
}

$tariffModel = new Tariff();
$tariffs = $tariffModel->getAll();
$userTariff = $user ? $tariffModel->getUserTariff($user['id']) : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding-top: 20px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .tariffs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .tariff-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .tariff-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .tariff-card.popular {
            border: 3px solid #ffd700;
            transform: scale(1.05);
        }
        
        .tariff-card.popular:hover {
            transform: scale(1.05) translateY(-10px);
        }
        
        .popular-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background: #ffd700;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .tariff-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .tariff-price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #3498db;
        }
        
        .tariff-price .period {
            font-size: 1rem;
            color: #7f8c8d;
        }
        
        .tariff-price .free {
            color: #2ecc71;
        }
        
        .tariff-description {
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.6;
            flex: 1;
        }
        
        .tariff-features {
            margin-bottom: 30px;
        }
        
        .feature {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .feature i {
            color: #2ecc71;
            margin-right: 10px;
            width: 20px;
        }
        
        .feature.disabled i {
            color: #e74c3c;
        }
        
        .feature.disabled {
            opacity: 0.6;
        }
        
        .feature .feature-value {
            margin-left: auto;
            font-weight: 600;
            color: #3498db;
        }
        
        .btn-choose {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        
        .btn-choose:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-choose.current {
            background: #27ae60;
            cursor: default;
        }
        
        .btn-choose.current:hover {
            transform: none;
            box-shadow: none;
        }
        
        .btn-choose.disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        .btn-choose.disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .comparison-table {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 40px;
            overflow-x: auto;
        }
        
        .comparison-table h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .feature-name {
            font-weight: 500;
        }
        
        .feature-value {
            text-align: center;
        }
        
        .feature-value.yes {
            color: #2ecc71;
            font-weight: 600;
        }
        
        .feature-value.no {
            color: #e74c3c;
        }
        
        .feature-value.number {
            color: #3498db;
            font-weight: 600;
        }
        
        .footer {
            text-align: center;
            color: white;
            padding: 20px;
            opacity: 0.8;
        }
        
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 30px;
            font-size: 16px;
            transition: opacity 0.3s;
        }
        
        .back-link:hover {
            opacity: 0.8;
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .tariffs-grid {
                grid-template-columns: 1fr;
            }
            
            .tariff-card.popular {
                transform: none;
            }
            
            .tariff-card.popular:hover {
                transform: translateY(-10px);
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
        
        .current-user {
            text-align: center;
            color: white;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        
        .current-user h3 {
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .current-user .tariff-name {
            color: #ffd700;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/" class="back-link">
            <i class="fas fa-arrow-left"></i> Вернуться на главную
        </a>
        
        <div class="header">
            <h1>Выберите свой тариф</h1>
            <p>Расширяйте возможности ваших презентаций с нашими гибкими тарифными планами</p>
        </div>
        
        <?php if ($user && $userTariff): ?>
        <div class="current-user">
            <h3>Ваш текущий тариф:</h3>
            <div class="tariff-name"><?php echo $userTariff['name']; ?></div>
            <?php if ($userTariff['price'] > 0): ?>
                <p>Следующее списание: <?php echo date('d.m.Y', strtotime('+30 days')); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="tariffs-grid">
            <?php foreach ($tariffs as $index => $tariff): ?>
            <div class="tariff-card <?php echo $index == 1 ? 'popular' : ''; ?>">
                <?php if ($index == 1): ?>
                <div class="popular-badge">ПОПУЛЯРНЫЙ</div>
                <?php endif; ?>
                
                <div class="tariff-name"><?php echo $tariff['name']; ?></div>
                
                <div class="tariff-price">
                    <?php if ($tariff['price'] == 0): ?>
                        <span class="free">Бесплатно</span>
                    <?php else: ?>
                        <?php echo number_format($tariff['price'], 0, '.', ' '); ?> ₽
                        <span class="period">/месяц</span>
                    <?php endif; ?>
                </div>
                
                <div class="tariff-description">
                    <?php echo $tariff['description']; ?>
                </div>
                
                <div class="tariff-features">
                    <div class="feature">
                        <i class="fas <?php echo $tariff['max_presentations'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <span>Макс. презентаций</span>
                        <span class="feature-value">
                            <?php echo $tariff['max_presentations'] == 0 ? '∞' : $tariff['max_presentations']; ?>
                        </span>
                    </div>
                    
                    <div class="feature <?php echo $tariff['can_print'] ? '' : 'disabled'; ?>">
                        <i class="fas <?php echo $tariff['can_print'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <span>Экспорт в PDF</span>
                        <span class="feature-value">
                            <?php echo $tariff['can_print'] ? '✓' : '✗'; ?>
                        </span>
                    </div>
                    
                    <div class="feature <?php echo $tariff['can_share'] ? '' : 'disabled'; ?>">
                        <i class="fas <?php echo $tariff['can_share'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <span>Поделиться презентацией</span>
                        <span class="feature-value">
                            <?php echo $tariff['can_share'] ? '✓' : '✗'; ?>
                        </span>
                    </div>
                    
                    <div class="feature <?php echo $tariff['can_public_link'] ? '' : 'disabled'; ?>">
                        <i class="fas <?php echo $tariff['can_public_link'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <span>Публичные ссылки</span>
                        <span class="feature-value">
                            <?php echo $tariff['can_public_link'] ? $tariff['max_public_links'] : '✗'; ?>
                        </span>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-palette"></i>
                        <span>Настройка цветов</span>
                        <span class="feature-value">✓</span>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-images"></i>
                        <span>Загрузка изображений</span>
                        <span class="feature-value">✓</span>
                    </div>
                </div>
                
                <?php if ($user): ?>
                    <?php if ($userTariff && $userTariff['id'] == $tariff['id']): ?>
                    <button class="btn-choose current">
                        <i class="fas fa-check-circle"></i> Текущий тариф
                    </button>
                    <?php elseif ($tariff['price'] == 0): ?>
                    <button class="btn-choose disabled">
                        Уже доступен
                    </button>
                    <?php else: ?>
                    <button class="btn-choose" onclick="selectTariff(<?php echo $tariff['id']; ?>)">
                        Выбрать тариф
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                <a href="/auth/register.php" class="btn-choose">
                    Зарегистрироваться
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="comparison-table">
            <h2>Сравнение тарифов</h2>
            <table>
                <thead>
                    <tr>
                        <th>Функция</th>
                        <?php foreach ($tariffs as $tariff): ?>
                        <th><?php echo $tariff['name']; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="feature-name">Цена в месяц</td>
                        <?php foreach ($tariffs as $tariff): ?>
                        <td class="feature-value number">
                            <?php echo $tariff['price'] == 0 ? 'Бесплатно' : number_format($tariff['price'], 0, '.', ' ') . ' ₽'; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="feature-name">Максимум презентаций</td>
                        <?php foreach ($tariffs as $tariff): ?>
                        <td class="feature-value number">
                            <?php echo $tariff['max_presentations'] == 0 ? '∞' : $tariff['max_presentations']; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="feature-name">Экспорт в PDF</td>
                        <?php foreach ($tariffs as $tariff): ?>
                        <td class="feature-value <?php echo $tariff['can_print'] ? 'yes' : 'no'; ?>">
                            <?php echo $tariff['can_print'] ? '✓' : '✗'; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="feature-name">Поделиться презентацией</td>
                        <?php foreach ($tariffs as $tariff): ?>
                        <td class="feature-value <?php echo $tariff['can_share'] ? 'yes' : 'no'; ?>">
                            <?php echo $tariff['can_share'] ? '✓' : '✗'; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="feature-name">Публичные ссылки</td>
                        <?php foreach ($tariffs as $tariff): ?>
                        <td class="feature-value number">
                            <?php echo $tariff['can_public_link'] ? $tariff['max_public_links'] : '—'; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="feature-name">Настройка цветов</td>
                        <?php foreach ($tariffs as $tariff): ?>
                        <td class="feature-value yes">✓</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="feature-name">Поддержка</td>
                        <?php foreach ($tariffs as $tariff): ?>
                        <td class="feature-value <?php echo $tariff['price'] > 0 ? 'yes' : 'no'; ?>">
                            <?php echo $tariff['price'] > 0 ? 'Приоритет' : 'Базовая'; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Все права защищены.</p>
            <p>Вопросы? Напишите нам на <a href="mailto:<?php echo SUPPORT_EMAIL; ?>" style="color: #ffd700;"><?php echo SUPPORT_EMAIL; ?></a></p>
        </div>
    </div>
    
    <script>
    function selectTariff(tariffId) {
        if (!confirm('Вы уверены, что хотите выбрать этот тариф?\n\nПосле подтверждения вы будете перенаправлены на страницу оплаты.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('tariff_id', tariffId);
        formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
        
        fetch('/api.php?action=create_payment', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                } else {
                    alert('Тариф успешно активирован!');
                    location.reload();
                }
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при выполнении запроса');
        });
    }
    
    // Плавный скролл к таблице сравнения
    document.querySelectorAll('.btn-choose').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (this.classList.contains('current') || this.classList.contains('disabled')) {
                e.preventDefault();
                document.querySelector('.comparison-table').scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    </script>
</body>
</html>