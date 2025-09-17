<?php
// 建築物詳細ページ
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/functions_new.php';

// 言語設定
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['ja', 'en']) ? $_GET['lang'] : 'ja';

// 建築物の取得
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (!$slug) {
    header('Location: index.php?lang=' . $lang);
    exit;
}

$building = getBuildingBySlugNew($slug, $lang);

// デバッグ情報
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo "<!-- Debug: Looking for slug: " . htmlspecialchars($slug) . " -->";
    echo "<!-- Debug: Building found: " . ($building ? 'Yes' : 'No') . " -->";
    
    // データベースで直接確認
    $db = getDB();
    $testStmt = $db->prepare("SELECT building_id, slug, title FROM buildings_table_2 WHERE slug = ? LIMIT 5");
    $testStmt->execute([$slug]);
    $testResults = $testStmt->fetchAll();
    echo "<!-- Debug: Direct DB query results: " . print_r($testResults, true) . " -->";
    
    if ($building) {
        echo "<!-- Debug: Building data: " . print_r($building, true) . " -->";
    }
}

if (!$building) {
    // デバッグモードの場合はエラーメッセージを表示
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<h1>建築物が見つかりませんでした</h1>";
        echo "<p>スラッグ: " . htmlspecialchars($slug) . "</p>";
        echo "<p><a href='index.php?lang=" . $lang . "'>検索ページに戻る</a></p>";
        exit;
    }
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
    <link rel="icon" href="assets/images/landmark.svg" type="image/svg+xml">
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
                        <?php if (!empty($building['thumbnailUrl'])): ?>
                            <img src="<?php echo htmlspecialchars($building['thumbnailUrl']); ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo htmlspecialchars($building['title']); ?>">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                 style="height: 300px;">
                                <img src="assets/images/landmark.svg" 
                                     alt="PocketNavi" 
                                     style="width: 120px; height: 120px; opacity: 0.3;">
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
                                <?php 
                                $location = $lang === 'ja' ? $building['location'] : $building['locationEn'];
                                
                                // デバッグ情報（開発時のみ）
                                if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                                    echo "<!-- Debug Location: lang=" . $lang . ", location=" . htmlspecialchars($building['location']) . ", locationEn=" . htmlspecialchars($building['locationEn']) . " -->";
                                }
                                
                                if ($location): 
                                ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo t('location', $lang); ?>
                                        </h6>
                                        <p class="mb-0"><?php echo htmlspecialchars($location); ?></p>
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
                                <?php 
                                $buildingTypes = $lang === 'ja' ? $building['buildingTypes'] : $building['buildingTypesEn'];
                                if (!empty($buildingTypes)): 
                                ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-building me-1"></i>
                                            <?php echo t('buildingTypes', $lang); ?>
                                        </h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($buildingTypes as $type): ?>
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
                                        <?php if (!empty($building['thumbnailUrl'])): ?>
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
                                        
                                        <?php if (empty($building['thumbnailUrl']) && !$building['youtubeUrl']): ?>
                                            <span class="text-muted"><?php echo $lang === 'ja' ? 'なし' : 'None'; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- YouTube Video -->
                                <?php if ($building['youtubeUrl']): ?>
                                    <div class="mt-3">
                                        <h6 class="text-muted mb-2"><?php echo t('videos', $lang); ?></h6>
                                        <div class="ratio ratio-16x9">
                                            <?php
                                            // YouTube URLを埋め込み用URLに変換
                                            $youtubeUrl = $building['youtubeUrl'];
                                            $embedUrl = '';
                                            
                                            // 既に埋め込み用URLの場合
                                            if (strpos($youtubeUrl, 'embed/') !== false) {
                                                $embedUrl = $youtubeUrl;
                                            }
                                            // YouTube Shorts URLの場合
                                            elseif (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/', $youtubeUrl, $matches)) {
                                                $videoId = $matches[1];
                                                $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                                            }
                                            // 通常のYouTube URLの場合
                                            elseif (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $youtubeUrl, $matches)) {
                                                $videoId = $matches[1];
                                                $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                                            }
                                            // その他の場合は元のURLをそのまま使用
                                            else {
                                                $embedUrl = $youtubeUrl;
                                            }
                                            ?>
                                            <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" 
                                                    title="<?php echo htmlspecialchars($building['title']); ?>" 
                                                    allowfullscreen
                                                    frameborder="0"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
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

