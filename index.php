<?php
// PocketNavi PHP版 - メインページ
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/functions_new.php';

// 言語設定（URLクエリパラメータから取得）
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['ja', 'en']) ? $_GET['lang'] : 'ja';

// 検索パラメータの取得
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$hasPhotos = isset($_GET['photos']) && $_GET['photos'] ? true : false;
$hasVideos = isset($_GET['videos']) && $_GET['videos'] ? true : false;

// デバッグ情報
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("Debug - hasPhotos: " . ($hasPhotos ? 'true' : 'false') . " (raw: " . ($_GET['photos'] ?? 'not set') . ")");
    error_log("Debug - hasVideos: " . ($hasVideos ? 'true' : 'false') . " (raw: " . ($_GET['videos'] ?? 'not set') . ")");
    error_log("Debug - query: " . ($query ?: 'empty'));
    error_log("Debug - GET params: " . print_r($_GET, true));
}
$userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$userLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$radiusKm = isset($_GET['radius']) ? max(1, intval($_GET['radius'])) : 5;
$buildingSlug = isset($_GET['building_slug']) ? trim($_GET['building_slug']) : '';
$prefectures = isset($_GET['prefectures']) ? trim($_GET['prefectures']) : '';
$architectsSlug = isset($_GET['architects_slug']) ? trim($_GET['architects_slug']) : '';
$completionYears = isset($_GET['completionYears']) ? trim($_GET['completionYears']) : '';

// 検索結果の取得
$buildings = [];
$totalBuildings = 0;
$totalPages = 0;
$currentPage = $page;
$limit = 10;
$currentBuilding = null; // 個別建築物データ（Mapボタン用）

if ($buildingSlug) {
    // 建物slug検索
    $searchResult = searchBuildingsBySlug($buildingSlug, $lang, $limit);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
    $currentPage = $searchResult['currentPage'];
    
    // 個別建築物の詳細データを取得（Mapボタン用）
    $currentBuilding = getBuildingBySlugNew($buildingSlug, $lang);
} elseif ($architectsSlug) {
    // 建築家slug検索
    $searchResult = searchBuildingsByArchitectSlug($architectsSlug, $lang, $limit, $page);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
    $currentPage = $searchResult['currentPage'];
    $architectInfo = $searchResult['architectInfo'];
} elseif ($completionYears) {
    // 建築年検索
    $searchResult = searchBuildingsByCompletionYear($completionYears, $page, $lang, $limit);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
    $currentPage = $searchResult['currentPage'];
} elseif ($prefectures) {
    // 都道府県検索
    $searchResult = searchBuildingsByPrefecture($prefectures, $page, $lang, $limit);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
    $currentPage = $searchResult['currentPage'];
} elseif ($userLat !== null && $userLng !== null) {
    // 現在地検索
    $searchResult = searchBuildingsByLocation($userLat, $userLng, $radiusKm, $page, $hasPhotos, $hasVideos, $lang, $limit);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
    $currentPage = $searchResult['currentPage'];
} elseif ($query || $hasPhotos || $hasVideos) {
    // キーワード検索またはメディアフィルター検索
    $searchResult = searchBuildingsNew($query, $page, $hasPhotos, $hasVideos, $lang, $limit);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
    $currentPage = $searchResult['currentPage'];
} else {
    // トップページ：has_photo優先順序で建築物を取得
    $searchResult = searchBuildingsNew('', $page, false, false, $lang, $limit);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
    $currentPage = $searchResult['currentPage'];
}

// 人気検索の取得
$popularSearches = getPopularSearches($lang);

// デバッグ情報の取得（開発時のみ）
$debugInfo = null;
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    $debugInfo = debugDatabase();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ja' ? 'PocketNavi - 建築物ナビゲーション' : 'PocketNavi - Building Navigation'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
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
                <!-- Search Form -->
                <?php include 'includes/search_form.php'; ?>
                
                <!-- Current Search Context Display -->
                <?php if ($architectsSlug && isset($architectInfo) && $architectInfo): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h4 mb-2">
                                <i data-lucide="circle-user-round" class="me-2" style="width: 20px; height: 20px;"></i>
                                <?php echo $lang === 'ja' ? '建築家' : 'Architect'; ?>: 
                                <span class="text-primary"><?php echo htmlspecialchars($architectInfo['name_ja'] ?? $architectInfo['name_en'] ?? ''); ?></span>
                            </h2>
                            <?php if (!empty($architectInfo['name_en']) && $architectInfo['name_ja'] !== $architectInfo['name_en']): ?>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($architectInfo['name_en']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($buildingSlug && $currentBuilding): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h4 mb-2">
                                <i data-lucide="building" class="me-2" style="width: 20px; height: 20px;"></i>
                                <?php echo $lang === 'ja' ? '建築物' : 'Building'; ?>: 
                                <span class="text-primary"><?php echo htmlspecialchars($currentBuilding['title'] ?? ''); ?></span>
                            </h2>
                            <?php if (!empty($currentBuilding['titleEn']) && $currentBuilding['title'] !== $currentBuilding['titleEn']): ?>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($currentBuilding['titleEn']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Image Search Links -->
                            <div class="mt-3">
                                <p class="mb-2">
                                    <i data-lucide="search" class="me-1" style="width: 16px; height: 16px;"></i>
                                    <?php echo $lang === 'ja' ? '画像検索で見る' : 'View in Image Search'; ?>:
                                </p>
                                <div class="d-flex gap-3 flex-wrap">
                                    <?php 
                                    $buildingName = $currentBuilding['title'] ?? '';
                                    $encodedName = urlencode($buildingName);
                                    ?>
                                    <a href="https://www.google.com/search?q=<?php echo $encodedName; ?>&tbm=isch" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i data-lucide="external-link" class="me-1" style="width: 14px; height: 14px;"></i>
                                        Google画像検索
                                    </a>
                                    <a href="https://www.bing.com/images/search?q=<?php echo $encodedName; ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i data-lucide="external-link" class="me-1" style="width: 14px; height: 14px;"></i>
                                        Microsoft Bing画像検索
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Video Links -->
                            <?php if (!empty($currentBuilding['youtubeUrl'])): ?>
                                <div class="mt-3">
                                    <p class="mb-2">
                                        <i data-lucide="video" class="me-1" style="width: 16px; height: 16px;"></i>
                                        <?php echo $lang === 'ja' ? '動画で見る' : 'View in Video'; ?>:
                                    </p>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <a href="<?php echo htmlspecialchars($currentBuilding['youtubeUrl']); ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-danger btn-sm">
                                            <i data-lucide="youtube" class="me-1" style="width: 14px; height: 14px;"></i>
                                            Youtubeで見る
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Debug Information -->
                <?php if ($debugInfo): ?>
                    <div class="alert alert-warning">
                        <h6>デバッグ情報:</h6>
                        <ul>
                            <li>総建築物数: <?php echo $debugInfo['total_buildings']; ?></li>
                            <li>座標がある建築物数: <?php echo $debugInfo['buildings_with_coords']; ?></li>
                            <li>東京の建築物数: <?php echo $debugInfo['tokyo_buildings']; ?></li>
                            <li>検索テスト結果: <?php echo $debugInfo['search_test_result']; ?></li>
                        </ul>
                        <small>URLに?debug=1を追加するとこの情報が表示されます</small>
                    </div>
                <?php endif; ?>
                
                <!-- Search Debug Information -->
                <?php if (($query || $architectsSlug || $completionYears || $hasPhotos || $hasVideos) && isset($_GET['debug'])): ?>
                    <div class="alert alert-info">
                        <h6>検索デバッグ情報:</h6>
                        <ul>
                            <?php if ($query): ?>
                                <li>検索クエリ: "<?php echo htmlspecialchars($query); ?>"</li>
                            <?php endif; ?>
                            <?php if ($architectsSlug): ?>
                                <li>建築家スラッグ: "<?php echo htmlspecialchars($architectsSlug); ?>"</li>
                            <?php endif; ?>
                            <?php if ($completionYears): ?>
                                <li>建築年: "<?php echo htmlspecialchars($completionYears); ?>"</li>
                            <?php endif; ?>
                            <?php if ($hasPhotos): ?>
                                <li>写真フィルター: 有効</li>
                            <?php endif; ?>
                            <?php if ($hasVideos): ?>
                                <li>動画フィルター: 有効</li>
                            <?php endif; ?>
                            <li>検索結果数: <?php echo count($buildings); ?></li>
                            <li>総件数: <?php echo $totalBuildings; ?></li>
                            <li>現在のページ: <?php echo $page; ?></li>
                            <li>総ページ数: <?php echo $totalPages; ?></li>
                            <li>リミット: <?php echo $limit; ?></li>
                        </ul>
                        <p><strong>注意:</strong> エラーログを確認してください（通常は C:\xampp\apache\logs\error.log）</p>
                    </div>
                <?php endif; ?>
                
                <!-- Search Results Header -->
                <?php if ($hasPhotos || $hasVideos): ?>
                    <div class="alert alert-light mb-3">
                        <h6 class="mb-2">
                            <i data-lucide="filter" class="me-2" style="width: 16px; height: 16px;"></i>
                            <?php echo $lang === 'ja' ? 'フィルター適用済み' : 'Filters Applied'; ?>
                        </h6>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php if ($hasPhotos): ?>
                                <span class="architect-badge filter-badge">
                                    <i data-lucide="image" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo $lang === 'ja' ? '写真あり' : 'With Photos'; ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['photos' => null])); ?>" 
                                       class="filter-remove-btn ms-2" 
                                       title="<?php echo $lang === 'ja' ? 'フィルターを解除' : 'Remove filter'; ?>">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($hasVideos): ?>
                                <span class="architect-badge filter-badge">
                                    <i data-lucide="youtube" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo $lang === 'ja' ? '動画あり' : 'With Videos'; ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['videos' => null])); ?>" 
                                       class="filter-remove-btn ms-2" 
                                       title="<?php echo $lang === 'ja' ? 'フィルターを解除' : 'Remove filter'; ?>">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Debug Information for Media Filters -->
                <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
                    <div class="alert alert-warning">
                        <h6>メディアフィルターデバッグ情報:</h6>
                        <ul>
                            <li>hasPhotos: <?php echo $hasPhotos ? 'true' : 'false'; ?></li>
                            <li>hasVideos: <?php echo $hasVideos ? 'true' : 'false'; ?></li>
                            <li>検索条件: <?php echo $query || $hasPhotos || $hasVideos ? 'メディアフィルター検索' : 'トップページ'; ?></li>
                            <li>検索結果数: <?php echo count($buildings); ?></li>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Building Cards -->
                <div class="row" id="building-cards">
                    <?php if (empty($buildings)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <?php echo $lang === 'ja' ? '建築物が見つかりませんでした。' : 'No buildings found.'; ?>
                                <?php if ($query): ?>
                                    <br><small>検索キーワード: "<?php echo htmlspecialchars($query); ?>"</small>
                                <?php endif; ?>
                                <?php if ($hasPhotos): ?>
                                    <br><small>写真フィルター: 有効</small>
                                <?php endif; ?>
                                <?php if ($hasVideos): ?>
                                    <br><small>動画フィルター: 有効</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($buildings as $index => $building): ?>
                            <div class="col-12 mb-4">
                                <?php 
                                // 通し番号を計算
                                $globalIndex = ($currentPage - 1) * $limit + $index + 1;
                                ?>
                                <?php include 'includes/building_card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <?php include 'includes/pagination.php'; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <?php include 'includes/sidebar.php'; ?>
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
        // ページ情報をJavaScriptに渡す
        window.pageInfo = {
            currentPage: <?php echo $currentPage; ?>,
            limit: <?php echo $limit; ?>
        };
        console.log('Page info set:', window.pageInfo); // デバッグ用
        
        // Lucideアイコンの初期化
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>

