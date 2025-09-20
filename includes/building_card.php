<?php 
// デバッグ用：建築物データの構造を確認
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo "<!-- Building data debug: " . print_r($building, true) . " -->";
}

// デバッグ：titleEnの値を確認
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo "<!-- titleEn debug: " . ($building['titleEn'] ?? 'NULL') . " -->";
}
?>
<!-- Building Card - Horizontal Layout -->
<div class="card mb-3 building-card" 
     data-building-id="<?php echo htmlspecialchars($building['building_id'] ?? ''); ?>"
     data-lat="<?php echo htmlspecialchars($building['lat'] ?? ''); ?>"
     data-lng="<?php echo htmlspecialchars($building['lng'] ?? ''); ?>"
     data-title="<?php echo htmlspecialchars($building['title'] ?? ''); ?>"
     data-title-en="<?php echo htmlspecialchars($building['titleEn'] ?? ''); ?>"
     data-location="<?php echo htmlspecialchars($building['location'] ?? ''); ?>"
     data-location-en="<?php echo htmlspecialchars($building['locationEn'] ?? ''); ?>"
     data-slug="<?php echo htmlspecialchars($building['slug'] ?? ''); ?>"
     data-popup-content="<?php echo htmlspecialchars(generatePopupContent($building, $lang ?? 'ja')); ?>">
    <div class="row g-0">
        <!-- Image Column -->
        <div class="col-md-3">
            <?php if (!empty($building['thumbnailUrl'])): ?>
                <img src="<?php echo htmlspecialchars($building['thumbnailUrl']); ?>" 
                     class="img-fluid rounded-start h-100" 
                     alt="<?php echo htmlspecialchars($building['title']); ?>"
                     style="height: 150px; object-fit: cover; width: 100%;">
            <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center rounded-start" 
                     style="height: 150px;">
                    <img src="/assets/images/landmark.svg" 
                         alt="PocketNavi" 
                         style="width: 60px; height: 60px; opacity: 0.3;">
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Content Column -->
        <div class="col-md-9">
    
            <div class="card-body d-flex flex-column h-100">
                <div class="d-flex align-items-center mb-2">
                    <div class="search-number-badge me-2">
                        <?php echo isset($globalIndex) ? $globalIndex : ($index + 1); ?>
                    </div>
                    <h5 class="card-title mb-0 flex-grow-1">
                        <a href="buildings/<?php echo urlencode($building['slug']); ?>" 
                           class="text-decoration-none text-dark">
                            <?php echo htmlspecialchars($lang === 'ja' ? $building['title'] : $building['titleEn']); ?>
                        </a>
                    </h5>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <?php if (!empty($building['architects'])): ?>
                            <div class="card-text mb-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($building['architects'] as $architect): ?>
                                        <?php 
                                        // 現在の検索条件を保持したURLパラメータを作成
                                        $urlParams = ['architects_slug' => $architect['slug'], 'lang' => $lang];
                                        if (isset($_GET['q']) && $_GET['q']) {
                                            $urlParams['q'] = $_GET['q'];
                                        }
                                        if (isset($_GET['prefectures']) && $_GET['prefectures']) {
                                            $urlParams['prefectures'] = $_GET['prefectures'];
                                        }
                                        if (isset($_GET['completionYears']) && $_GET['completionYears']) {
                                            $urlParams['completionYears'] = $_GET['completionYears'];
                                        }
                                        if (isset($_GET['photos']) && $_GET['photos']) {
                                            $urlParams['photos'] = $_GET['photos'];
                                        }
                                        if (isset($_GET['videos']) && $_GET['videos']) {
                                            $urlParams['videos'] = $_GET['videos'];
                                        }
                                        ?>
                                        <a href="/index.php?<?php echo http_build_query($urlParams); ?>" 
                                           class="architect-badge text-decoration-none">
                                            <i data-lucide="circle-user-round" class="me-1" style="width: 12px; height: 12px;"></i>
                                            <?php echo htmlspecialchars($lang === 'ja' ? $architect['architectJa'] : $architect['architectEn']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($building['location']): ?>
                            <p class="card-text mb-1">
                                <small class="text-muted">
                                    <i data-lucide="map-pin" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo htmlspecialchars($lang === 'ja' ? $building['location'] : $building['locationEn']); ?>
                                    <?php if (isset($building['distance'])): ?>
                                        <span class="ms-2"><i class="fas fa-route me-1"></i><?php echo $building['distance']; ?>km</span>
                                    <?php endif; ?>
                                </small>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4 text-end">
                        
                        <!-- 右端のバッジは削除（右下のボタンと重複のため） -->
                    </div>
                </div>
                
                <?php 
                $buildingTypesRaw = $lang === 'ja' ? $building['buildingTypes'] : $building['buildingTypesEn'];
                if (is_array($buildingTypesRaw)) {
                    $buildingTypes = $buildingTypesRaw;
                } else {
                    $buildingTypes = !empty($buildingTypesRaw) ? explode(',', $buildingTypesRaw) : [];
                }
                if (!empty($buildingTypes)): 
                ?>
                    <div class="mt-2">
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($buildingTypes as $type): ?>
                                <?php 
                                // 現在の検索条件を保持したURLパラメータを作成
                                $urlParams = ['q' => $type, 'lang' => $lang];
                                if (isset($_GET['prefectures']) && $_GET['prefectures']) {
                                    $urlParams['prefectures'] = $_GET['prefectures'];
                                }
                                if (isset($_GET['completionYears']) && $_GET['completionYears']) {
                                    $urlParams['completionYears'] = $_GET['completionYears'];
                                }
                                if (isset($_GET['photos']) && $_GET['photos']) {
                                    $urlParams['photos'] = $_GET['photos'];
                                }
                                if (isset($_GET['videos']) && $_GET['videos']) {
                                    $urlParams['videos'] = $_GET['videos'];
                                }
                                ?>
                                <a href="/index.php?<?php echo http_build_query($urlParams); ?>" 
                                   class="building-type-badge text-decoration-none"
                                   title="<?php echo $lang === 'ja' ? 'この用途で検索' : 'Search by this building type'; ?>">
                                    <i data-lucide="building" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo htmlspecialchars($type); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($building['prefectures']) || $building['completionYears']): ?>
                    <div class="mt-2">
                        <div class="d-flex flex-wrap gap-1">
                            <?php if (!empty($building['prefectures'])): ?>
                                <?php 
                                // 現在の検索条件を保持したURLパラメータを作成
                                $urlParams = ['prefectures' => $building['prefecturesEn'], 'lang' => $lang];
                                if (isset($_GET['q']) && $_GET['q']) {
                                    $urlParams['q'] = $_GET['q'];
                                }
                                if (isset($_GET['completionYears']) && $_GET['completionYears']) {
                                    $urlParams['completionYears'] = $_GET['completionYears'];
                                }
                                if (isset($_GET['photos']) && $_GET['photos']) {
                                    $urlParams['photos'] = $_GET['photos'];
                                }
                                if (isset($_GET['videos']) && $_GET['videos']) {
                                    $urlParams['videos'] = $_GET['videos'];
                                }
                                ?>
                                <a href="/index.php?<?php echo http_build_query($urlParams); ?>" 
                                   class="prefecture-badge text-decoration-none">
                                    <i data-lucide="map-pin" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo htmlspecialchars($lang === 'ja' ? $building['prefectures'] : $building['prefecturesEn']); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($building['completionYears']): ?>
                                <?php 
                                // 現在の検索条件を保持したURLパラメータを作成
                                $urlParams = ['completionYears' => $building['completionYears'], 'lang' => $lang];
                                if (isset($_GET['q']) && $_GET['q']) {
                                    $urlParams['q'] = $_GET['q'];
                                }
                                if (isset($_GET['prefectures']) && $_GET['prefectures']) {
                                    $urlParams['prefectures'] = $_GET['prefectures'];
                                }
                                if (isset($_GET['photos']) && $_GET['photos']) {
                                    $urlParams['photos'] = $_GET['photos'];
                                }
                                if (isset($_GET['videos']) && $_GET['videos']) {
                                    $urlParams['videos'] = $_GET['videos'];
                                }
                                ?>
                                <a href="/index.php?<?php echo http_build_query($urlParams); ?>" 
                                   class="completion-year-badge text-decoration-none"
                                   title="<?php echo $lang === 'ja' ? 'この建築年で検索' : 'Search by this completion year'; ?>">
                                    <i data-lucide="calendar" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo $building['completionYears']; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
                    <!-- デバッグ: 建築年データ -->
                    <div class="mt-2">
                        <small class="text-muted">
                            デバッグ - completionYears: <?php echo isset($building['completionYears']) ? $building['completionYears'] : '未設定'; ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <i data-lucide="heart" class="me-1" style="width: 14px; height: 14px;"></i>
                <?php echo $lang === 'ja' ? 'いいね' : 'Likes'; ?>: <?php echo $building['likes']; ?>
            </small>
            
            <div class="btn-group btn-group-sm">
                <?php if (!empty($building['thumbnailUrl'])): ?>
                    <button type="button" 
                            class="btn btn-outline-success btn-sm"
                            onclick="openPhoto('<?php echo htmlspecialchars($building['thumbnailUrl']); ?>')">
                        <i data-lucide="image" style="width: 16px; height: 16px;"></i>
                    </button>
                <?php endif; ?>
                
                <?php if ($building['youtubeUrl']): ?>
                    <button type="button" 
                            class="btn btn-outline-danger btn-sm"
                            onclick="openVideo('<?php echo htmlspecialchars($building['youtubeUrl']); ?>')">
                        <i data-lucide="youtube" style="width: 16px; height: 16px;"></i>
                    </button>
                <?php endif; ?>
                
                <button type="button" 
                        class="btn btn-outline-primary btn-sm"
                        onclick="showOnMap(<?php echo $building['lat']; ?>, <?php echo $building['lng']; ?>)">
                    <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

