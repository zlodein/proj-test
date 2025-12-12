<?php
// lk-functions/stats.php
function renderDashboardStats($presentations, $totalPresentations) {
    ob_start();
    ?>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-file-powerpoint"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $totalPresentations; ?></h3>
                <p>Всего презентаций</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count(array_filter($presentations, function($p) { return $p['status'] === 'published'; })); ?></h3>
                <p>Опубликовано</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-edit"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count(array_filter($presentations, function($p) { return $p['status'] === 'draft'; })); ?></h3>
                <p>Черновики</p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>