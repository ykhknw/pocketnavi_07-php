<!-- Sidebar -->
<div class="sticky-top" style="top: 20px;">
    <!-- Map -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <div id="map" style="height: 400px; width: 100%;"></div>
        </div>
    </div>
    
    <!-- Popular Searches -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-fire me-2"></i>
                <?php echo t('popularSearches', $lang); ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($popularSearches)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($popularSearches as $search): ?>
                        <a href="?q=<?php echo urlencode($search['query']); ?>&lang=<?php echo $lang; ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($search['query']); ?></span>
                            <span class="badge bg-primary rounded-pill"><?php echo $search['count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">
                    <?php echo $lang === 'ja' ? '人気の検索がありません。' : 'No popular searches available.'; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

