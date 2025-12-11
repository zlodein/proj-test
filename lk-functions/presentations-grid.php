<?php
// lk-functions/presentations-grid.php
function renderPresentationsGrid($presentations, $tariffModel) {
    $user = $GLOBALS['user'] ?? getCurrentUser();
    
    ob_start();
    ?>
    <div class="search-bar">
        <div class="search-input-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Поиск презентаций...">
        </div>
    </div>
    
    <div class="presentations-grid" id="presentationsGrid">
        <?php if (empty($presentations)): ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-file-powerpoint"></i>
                <h3>У вас пока нет презентаций</h3>
                <p>Создайте свою первую презентацию прямо сейчас</p>
                <?php if ($tariffModel->canCreatePresentation($user['id'])): ?>
                    <button class="btn-create" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Создать презентацию
                    </button>
                <?php else: ?>
                    <button class="btn-create" onclick="openUpgradeModal()" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
                        <i class="fas fa-crown"></i> Обновить тариф для создания
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($presentations as $presentation): ?>
                <div class="presentation-card" data-id="<?php echo $presentation['id']; ?>" data-title="<?php echo escape($presentation['title']); ?>">
                    <div class="presentation-thumbnail">
                        <?php if (!empty($presentation['cover_image'])): ?>
                            <?php 
                            $coverUrl = $presentation['cover_image'];
                            if (!preg_match('/^https?:\/\//', $coverUrl)) {
                                $coverUrl = APP_URL . $coverUrl;
                            }
                            ?>
                            <img src="<?php echo $coverUrl; ?>" 
                                 alt="<?php echo escape($presentation['title']); ?>" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <i class="fas fa-image" style="display: none;"></i>
                        <?php else: ?>
                            <i class="fas fa-image"></i>
                        <?php endif; ?>
                        <span class="presentation-status <?php echo $presentation['status'] === 'published' ? 'status-published' : 'status-draft'; ?>">
                            <?php echo $presentation['status'] === 'published' ? 'Опубликовано' : 'Черновик'; ?>
                        </span>
                    </div>
                    <div class="presentation-content">
                        <h3 class="presentation-title"><?php echo escape($presentation['title']); ?></h3>
                        <div class="presentation-meta">
                            <span><i class="far fa-clock"></i> <?php echo date('d.m.Y', strtotime($presentation['updated_at'])); ?></span>
                            <span><i class="fas fa-file-alt"></i> <?php echo $presentation['slides_count'] ?? 0; ?> слайдов</span>
                        </div>
                        <div class="presentation-actions">
                            <button class="btn-action btn-view" onclick="viewPresentation(<?php echo $presentation['id']; ?>)">
                                <i class="fas fa-eye"></i> Просмотр
                            </button>
                            <button class="btn-action btn-edit" onclick="editPresentation(<?php echo $presentation['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-delete" onclick="deletePresentation(<?php echo $presentation['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>