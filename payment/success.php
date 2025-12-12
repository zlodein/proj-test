<?php
// payment/success.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
requireAuth();

// Здесь YooKassa будет перенаправлять пользователя после успешной оплаты
// В реальном проекте нужно реализовать webhook для обработки уведомлений

$user = getCurrentUser();

// Показываем сообщение об успешной оплате
setFlash('success', 'Платеж успешно завершен! Ваш тариф будет обновлен в течение нескольких минут.');
redirect('/index.php');