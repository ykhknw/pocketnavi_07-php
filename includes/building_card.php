<!-- Building Card -->
<div class="card h-100 building-card" data-building-id="<?php echo $building['id']; ?>">
    <?php if ($building['thumbnailUrl']): ?>
        <img src="<?php echo htmlspecialchars($building['thumbnailUrl']); ?>" 
             class="card-img-top" 
             alt="<?php echo htmlspecialchars($building['title']); ?>"
             style="height: 200px; object-fit: cover;">
    <?php else: ?>
        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
             style="height: 200px;">
            <i class="fas fa-building fa-3x text-muted"></i>
        </div>
    <?php endif; ?>
    
    <div class="card-body d-flex flex-column">
        <h5 class="card-title">
            <a href="building.php?slug=<?php echo urlencode($building['slug']); ?>&lang=<?php echo $lang; ?>" 
               class="text-decoration-none text-dark">
                <?php echo htmlspecialchars($lang === 'ja' ? $building['title'] : $building['titleEn']); ?>
            </a>
        </h5>
        
        <?php if (!empty($building['architects'])): ?>
            <p class="card-text">
                <small class="text-muted">
                    <i class="fas fa-user me-1"></i>
                    <?php 
                    $architectNames = array_map(function($architect) use ($lang) {
                        return $lang === 'ja' ? $architect['architectJa'] : $architect['architectEn'];
                    }, $building['architects']);
                    echo htmlspecialchars(implode('、', $architectNames));
                    ?>
                </small>
            </p>
        <?php endif; ?>
        
        <?php if ($building['location']): ?>
            <p class="card-text">
                <small class="text-muted">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo htmlspecialchars($lang === 'ja' ? $building['location'] : $building['locationEn']); ?>
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
