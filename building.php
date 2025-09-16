<?php
// 建築物詳細ページ
require_once 'config/database.php';
require_once 'includes/functions.php';

// 言語設定
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['ja', 'en']) ? $_GET['lang'] : 'ja';

// 建築物の取得
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (!$slug) {
    header('Location: index.php?lang=' . $lang);
    exit;
}

$building = getBuildingBySlug($slug, $lang);
if (!$building) {
    header('Location: index.php?lang=' . $lang);
    exit;
}

// 写真の取得（実際のアプリでは別途実装）
$photos = [];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lang === 'ja' ? $building['title'] : $building['titleEn']); ?> - PocketNavi</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="mb-4">
                    <!-- Back Button -->
                    <a href="javascript:history.back()" class="btn btn-outline-secondary mb-3">
                        <i class="fas fa-arrow-left me-2"></i>
                        <?php echo t('backToList', $lang); ?>
                    </a>
                    
                    <h1 class="h2"><?php echo htmlspecialchars($lang === 'ja' ? $building['title'] : $building['titleEn']); ?></h1>
                </div>
                
                <!-- Building Details -->
                <div class="row">
                    <!-- Main Image -->
                    <div class="col-md-6 mb-4">
                        <?php if ($building['thumbnailUrl']): ?>
                            <img src="<?php echo htmlspecialchars($building['thumbnailUrl']); ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo htmlspecialchars($building['title']); ?>">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                 style="height: 300px;">
                                <i class="fas fa-building fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Details -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <!-- Architect -->
                                <?php if (!empty($building['architects'])): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo t('architect', $lang); ?>
                                        </h6>
                                        <p class="mb-0">
                                            <?php 
                                            $architectNames = array_map(function($architect) use ($lang) {
                                                return $lang === 'ja' ? $architect['architectJa'] : $architect['architectEn'];
                                            }, $building['architects']);
                                            echo htmlspecialchars(implode('、', $architectNames));
                                            ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Location -->
                                <?php if ($building['location']): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo t('location', $lang); ?>
                                        </h6>
                                        <p class="mb-0"><?php echo htmlspecialchars($lang === 'ja' ? $building['location'] : $building['locationEn']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Prefecture -->
                                <?php if ($building['prefectures']): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-map me-1"></i>
                                            <?php echo t('prefecture', $lang); ?>
                                        </h6>
                                        <p class="mb-0"><?php echo htmlspecialchars($lang === 'ja' ? $building['prefectures'] : $building['prefecturesEn']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Building Types -->
                                <?php if (!empty($building['buildingTypes'])): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-building me-1"></i>
                                            <?php echo t('buildingTypes', $lang); ?>
                                        </h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($building['buildingTypes'] as $type): ?>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($type); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Completion Year -->
                                <?php if ($building['completionYears']): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo t('completionYear', $lang); ?>
                                        </h6>
                                        <p class="mb-0"><?php echo $building['completionYears']; ?>年</p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Photos/Videos -->
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">
                                        <i class="fas fa-images me-1"></i>
                                        <?php echo t('photos', $lang); ?> / <?php echo t('videos', $lang); ?>
                                    </h6>
                                    <div class="d-flex gap-2">
                                        <?php if ($building['thumbnailUrl']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-image me-1"></i>
                                                <?php echo t('photos', $lang); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($building['youtubeUrl']): ?>
                                            <span class="badge bg-danger">
                                                <i class="fab fa-youtube me-1"></i>
                                                <?php echo t('videos', $lang); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!$building['thumbnailUrl'] && !$building['youtubeUrl']): ?>
                                            <span class="text-muted"><?php echo $lang === 'ja' ? 'なし' : 'None'; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- YouTube Video -->
                                <?php if ($building['youtubeUrl']): ?>
                                    <div class="mt-3">
                                        <h6 class="text-muted mb-2"><?php echo t('videos', $lang); ?></h6>
                                        <div class="ratio ratio-16x9">
                                            <iframe src="<?php echo htmlspecialchars($building['youtubeUrl']); ?>" 
                                                    title="<?php echo htmlspecialchars($building['title']); ?>" 
                                                    allowfullscreen></iframe>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">
                    <!-- Map -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-map me-2"></i>
                                <?php echo $lang === 'ja' ? '地図' : 'Map'; ?>
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="map" style="height: 400px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // 地図の初期化
        document.addEventListener('DOMContentLoaded', function() {
            const building = <?php echo json_encode($building); ?>;
            initMap([building.lat, building.lng], [building]);
        });
    </script>
</body>
</html>
