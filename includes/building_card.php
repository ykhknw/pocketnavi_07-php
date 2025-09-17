<?php 
// デバッグ用：建築物データの構造を確認
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo "<!-- Building data debug: " . print_r($building, true) . " -->";
}
?>
<!-- Building Card -->
<div class="card h-100 building-card" 
     data-building-id="<?php echo htmlspecialchars($building['building_id'] ?? ''); ?>"
     data-lat="<?php echo htmlspecialchars($building['lat'] ?? ''); ?>"
     data-lng="<?php echo htmlspecialchars($building['lng'] ?? ''); ?>"
     data-title="<?php echo htmlspecialchars($building['title'] ?? ''); ?>"
     data-title-en="<?php echo htmlspecialchars($building['titleEn'] ?? ''); ?>"
     data-location="<?php echo htmlspecialchars($building['location'] ?? ''); ?>"
     data-location-en="<?php echo htmlspecialchars($building['locationEn'] ?? ''); ?>"
     data-slug="<?php echo htmlspecialchars($building['slug'] ?? ''); ?>">
    <?php if (!empty($building['thumbnailUrl'])): ?>
        <img src="<?php echo htmlspecialchars($building['thumbnailUrl']); ?>" 
             class="card-img-top" 
             alt="<?php echo htmlspecialchars($building['title']); ?>"
             style="height: 200px; object-fit: cover;">
    <?php else: ?>
        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
             style="height: 200px;">
            <img src="assets/images/landmark.svg" 
                 alt="PocketNavi" 
                 style="width: 80px; height: 80px; opacity: 0.3;">
        </div>
    <?php endif; ?>
    
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
            <div class="search-number-badge me-2">
                <?php echo $index + 1; ?>
            </div>
            <h5 class="card-title mb-0 flex-grow-1">
                <a href="building.php?slug=<?php echo urlencode($building['slug']); ?>&lang=<?php echo $lang; ?>" 
                   class="text-decoration-none text-dark">
                    <?php echo htmlspecialchars($lang === 'ja' ? $building['title'] : $building['titleEn']); ?>
                </a>
            </h5>
        </div>
        
        <?php if (!empty($building['architects'])): ?>
            <div class="card-text mb-2">
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($building['architects'] as $architect): ?>
                        <a href="building.php?architects=<?php echo urlencode($architect['slug']); ?>&lang=<?php echo $lang; ?>" 
                           class="architect-badge text-decoration-none">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($lang === 'ja' ? $architect['architectJa'] : $architect['architectEn']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($building['location']): ?>
            <p class="card-text">
                <small class="text-muted">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo htmlspecialchars($lang === 'ja' ? $building['location'] : $building['locationEn']); ?>
                    <?php if (isset($building['distance'])): ?>
                        <br><i class="fas fa-route me-1"></i>
                        <?php echo $building['distance']; ?>km
                    <?php endif; ?>
                </small>
            </p>
        <?php endif; ?>
        
        <?php if ($building['completionYears']): ?>
            <p class="card-text">
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    <?php echo $building['completionYears']; ?>年
                </small>
            </p>
        <?php endif; ?>
        
        <?php if (!empty($building['buildingTypes'])): ?>
            <div class="mt-auto">
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($building['buildingTypes'] as $type): ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($type); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <?php echo $lang === 'ja' ? 'いいね' : 'Likes'; ?>: <?php echo $building['likes']; ?>
            </small>
            
            <div class="btn-group btn-group-sm">
                <?php if ($building['youtubeUrl']): ?>
                    <button type="button" 
                            class="btn btn-outline-danger btn-sm"
                            onclick="openVideo('<?php echo htmlspecialchars($building['youtubeUrl']); ?>')">
                        <i class="fab fa-youtube"></i>
                    </button>
                <?php endif; ?>
                
                <button type="button" 
                        class="btn btn-outline-primary btn-sm"
                        onclick="showOnMap(<?php echo $building['lat']; ?>, <?php echo $building['lng']; ?>)">
                    <i class="fas fa-map-marker-alt"></i>
                </button>
            </div>
        </div>
    </div>
</div>

