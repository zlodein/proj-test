<?php
// lk-functions/feature-requests.php
function renderFeatureRequests($userFeatures) {
    ob_start();
    ?>
    <!-- Блок пожеланий для сайта -->
    <div class="feature-requests">
        <div class="dashboard-title">
            <h2><i class="fas fa-lightbulb"></i> Пожелания для сайта</h2>
            <p>Предложите новые функции или улучшения</p>
        </div>
        
        <form id="featureRequestForm" style="margin-bottom: 20px;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <input type="text" name="title" class="form-control" placeholder="Краткое описание пожелания" required>
            </div>
            <div class="form-group">
                <textarea name="description" class="form-control" placeholder="Подробное описание (опционально)" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Добавить пожелание
            </button>
        </form>
        
        <div id="userFeaturesList">
            <?php foreach ($userFeatures as $feature): ?>
                <div class="feature-item <?php echo $feature['is_implemented'] ? 'completed' : ''; ?>" 
                     data-id="<?php echo $feature['id']; ?>">
                    <div class="feature-content">
                        <h4><?php echo escape($feature['title']); ?></h4>
                        <?php if ($feature['description']): ?>
                            <p><?php echo escape($feature['description']); ?></p>
                        <?php endif; ?>
                        <small class="feature-date">
                            <?php echo date('d.m.Y H:i', strtotime($feature['created_at'])); ?>
                            <?php if ($feature['status'] != 'new'): ?>
                                <span class="feature-status status-<?php echo $feature['status']; ?>">
                                    <?php echo $feature['status'] == 'in_progress' ? 'В работе' : 
                                           ($feature['status'] == 'completed' ? 'Реализовано' : 'Отклонено'); ?>
                                </span>
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="feature-vote">
                        <button class="vote-btn <?php echo $feature['user_voted'] ? 'voted' : ''; ?>" 
                                onclick="voteFeature(<?php echo $feature['id']; ?>, this)">
                            <i class="fas fa-thumbs-up"></i>
                        </button>
                        <span class="vote-count"><?php echo $feature['votes']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>