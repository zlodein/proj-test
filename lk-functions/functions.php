<?php
// lk-functions/functions.php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/stats.php';
require_once __DIR__ . '/presentations-grid.php';
require_once __DIR__ . '/feature-requests.php';
require_once __DIR__ . '/footer.php';

function renderDashboardPage($data) {
    // Сохраняем данные в глобальной области видимости для функций
    $GLOBALS['user'] = $data['user'];
    $GLOBALS['userTariff'] = $data['userTariff'];
    $GLOBALS['remainingPresentations'] = $data['remainingPresentations'];
    $GLOBALS['tariffModel'] = $data['tariffModel'];
    $GLOBALS['success'] = $data['success'] ?? null;
    $GLOBALS['error'] = $data['error'] ?? null;
    
    $output = '';
    $output .= renderDashboardHeader();
    $output .= renderDashboardStats($data['presentations'], $data['totalPresentations']);
    $output .= renderPresentationsGrid($data['presentations'], $data['tariffModel']);
    $output .= renderFeatureRequests($data['userFeatures']);
    $output .= renderDashboardFooter();
    
    return $output;
}
?>