<?php
// PocketNavi - Optimized Index Page (Phase 4.2)
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';
require_once '../src/Services/BuildingService.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// 言語設定（URLクエリパラメータから取得）
$lang = InputValidator::validateLanguage($_GET['lang'] ?? 'ja');

// ページタイトル
$pageTitle = $lang === 'ja' ? '建築検索' : 'Architecture Search';

// 検索パラメータの取得と検証
$query = InputValidator::validateString($_GET['q'] ?? '');
$completionYears = InputValidator::validateString($_GET['completionYears'] ?? '');
$prefectures = InputValidator::validateString($_GET['prefectures'] ?? '');
$buildingTypes = InputValidator::validateString($_GET['buildingTypes'] ?? '');
$hasPhotos = isset($_GET['hasPhotos']) && $_GET['hasPhotos'] === '1';
$hasVideos = isset($_GET['hasVideos']) && $_GET['hasVideos'] === '1';

// 位置情報検索のパラメータ
$userLat = InputValidator::validateFloat($_GET['lat'] ?? null);
$userLng = InputValidator::validateFloat($_GET['lng'] ?? null);
$radius = InputValidator::validateInt($_GET['radius'] ?? 5);

// ページネーション
$page = InputValidator::validateInt($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// データベース接続
$db = getDB();
$buildingService = new BuildingService($db);

// 検索実行
$searchResult = [];

if ($userLat && $userLng) {
    // 位置情報検索
    $searchResult = $buildingService->searchByLocation($userLat, $userLng, $radius, $page, $hasPhotos, $hasVideos, $lang, $limit);
} elseif (!empty($query) || !empty($completionYears) || !empty($prefectures) || !empty($buildingTypes) || $hasPhotos || $hasVideos) {
    // 複数条件検索
    $searchResult = $buildingService->searchWithMultipleConditions($query, $completionYears, $prefectures, $buildingTypes, $hasPhotos, $hasVideos, $page, $lang, $limit);
} else {
    // 全建築物取得
    $searchResult = $buildingService->getAllBuildings($page, $hasPhotos, $hasVideos, $lang, $limit);
}

$buildings = $searchResult['buildings'] ?? [];
$total = $searchResult['total'] ?? 0;
$totalPages = $searchResult['totalPages'] ?? 0;
$currentPage = $searchResult['currentPage'] ?? $page;

// 現在の建築物（詳細ページ用）
$currentBuilding = null;
if (count($buildings) === 1) {
    $currentBuilding = $buildings[0];
}

// ページ情報をJavaScriptに渡す
$pageInfo = [
    'currentPage' => $currentPage,
    'limit' => $limit,
    'total' => $total,
    'totalPages' => $totalPages
];

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <?php include '../src/Views/includes/optimized_head.php'; ?>
</head>
<body>
    <!-- Header -->
    <?php include '../src/Views/includes/header.php'; ?>
    
    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Search Form -->
                <?php include '../src/Views/includes/search_form.php'; ?>
                
                <!-- Search Results -->
                <div class="search-results">
                    <?php if (!empty($buildings)): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2>
                                <?php if ($userLat && $userLng): ?>
                                    <?php echo $lang === 'ja' ? '周辺の建築物' : 'Nearby Buildings'; ?>
                                    <small class="text-muted">(<?php echo $total; ?>件)</small>
                                <?php elseif (!empty($query)): ?>
                                    <?php echo $lang === 'ja' ? '検索結果' : 'Search Results'; ?>
                                    <small class="text-muted">"<?php echo htmlspecialchars($query); ?>" (<?php echo $total; ?>件)</small>
                                <?php else: ?>
                                    <?php echo $lang === 'ja' ? '建築物一覧' : 'Building List'; ?>
                                    <small class="text-muted">(<?php echo $total; ?>件)</small>
                                <?php endif; ?>
                            </h2>
                        </div>
                        
                        <!-- Building Cards -->
                        <div class="responsive-grid">
                            <?php foreach ($buildings as $index => $building): ?>
                                <?php include '../src/Views/includes/building_card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <?php include '../src/Views/includes/pagination.php'; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <h3><?php echo $lang === 'ja' ? '建築物が見つかりませんでした' : 'No buildings found'; ?></h3>
                            <p class="text-muted">
                                <?php echo $lang === 'ja' ? '検索条件を変更してお試しください。' : 'Please try changing your search criteria.'; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <?php include '../src/Views/includes/sidebar.php'; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../src/Views/includes/footer.php'; ?>
    
    <!-- Critical JavaScript (inline) -->
    <script>
        // Critical JavaScript for immediate functionality
        window.pageInfo = <?php echo json_encode($pageInfo); ?>;
        window.buildingsData = <?php echo json_encode($buildings); ?>;
        
        // Basic functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons for critical elements
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
    
    <!-- Lazy Loading Script -->
    <script src="/assets/js/lazy-load.js" defer></script>
    
    <!-- Non-critical JavaScript (deferred) -->
    <script data-src="/assets/js/main.min.js" defer></script>
    <script data-src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" defer></script>
    <script data-src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
