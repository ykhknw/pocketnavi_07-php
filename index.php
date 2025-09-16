<?php
// PocketNavi PHP版 - メインページ
require_once 'config/database.php';
require_once 'includes/functions.php';

// 言語設定（URLクエリパラメータから取得）
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['ja', 'en']) ? $_GET['lang'] : 'ja';

// 検索パラメータの取得
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$hasPhotos = isset($_GET['photos']) ? (bool)$_GET['photos'] : false;
$hasVideos = isset($_GET['videos']) ? (bool)$_GET['videos'] : false;

// 検索結果の取得
$buildings = [];
$totalBuildings = 0;
$totalPages = 0;

if ($query) {
    $searchResult = searchBuildings($query, $page, $hasPhotos, $hasVideos, $lang);
    $buildings = $searchResult['buildings'];
    $totalBuildings = $searchResult['total'];
    $totalPages = $searchResult['totalPages'];
} else {
    // 初期表示：最近の建築物を取得
    $buildings = getRecentBuildings(20, $lang);
    $totalBuildings = count($buildings);
    $totalPages = 1;
}

// 人気検索の取得
$popularSearches = getPopularSearches($lang);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ja' ? 'PocketNavi - 建築物ナビゲーション' : 'PocketNavi - Building Navigation'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <!-- Search Form -->
                <?php include 'includes/search_form.php'; ?>
                
                <!-- Building Cards -->
                <div class="row" id="building-cards">
                    <?php if (empty($buildings)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <?php echo $lang === 'ja' ? '建築物が見つかりませんでした。' : 'No buildings found.'; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($buildings as $index => $building): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
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
</body>
</html>
