<?php
// 建築家ページのテスト
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';
require_once '../src/Services/BuildingService.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// 言語設定
$lang = InputValidator::validateLanguage($_GET['lang'] ?? 'ja');

// 建築家スラッグを取得
$architectSlug = $_GET['architect_slug'] ?? 'u-architects';

// 検索結果の取得
$searchResult = searchBuildingsByArchitectSlug($architectSlug, 1, $lang, 10);
$buildings = $searchResult['buildings'] ?? [];
$total = $searchResult['total'] ?? 0;
$architectInfo = $searchResult['architectInfo'] ?? null;

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>建築家ページテスト - <?php echo htmlspecialchars($architectSlug); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
        <h1>建築家ページテスト</h1>
        <h2>建築家スラッグ: <?php echo htmlspecialchars($architectSlug); ?></h2>
        
        <?php if ($architectInfo): ?>
            <div class="alert alert-info">
                <h3>建築家情報</h3>
                <p><strong>日本語名:</strong> <?php echo htmlspecialchars($architectInfo['nameJa'] ?? ''); ?></p>
                <p><strong>英語名:</strong> <?php echo htmlspecialchars($architectInfo['nameEn'] ?? ''); ?></p>
            </div>
        <?php endif; ?>
        
        <h3>建築物リスト (<?php echo $total; ?>件)</h3>
        
        <?php if (!empty($buildings)): ?>
            <?php foreach ($buildings as $index => $building): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h4><?php echo htmlspecialchars($building['title']); ?></h4>
                        
                        <!-- 建築家データの詳細表示 -->
                        <h5>建築家データ（生データ）:</h5>
                        <pre><?php print_r($building['architects'] ?? []); ?></pre>
                        
                        <!-- 建築家バッジの表示 -->
                        <h5>建築家バッジ表示:</h5>
                        <?php if (!empty($building['architects'])): ?>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($building['architects'] as $architect): ?>
                                    <a href="/architects/<?php echo urlencode($architect['slug']); ?>/?lang=<?php echo $lang; ?>" 
                                       class="architect-badge text-decoration-none">
                                        <i data-lucide="circle-user-round" class="me-1" style="width: 12px; height: 12px;"></i>
                                        <?php echo htmlspecialchars($lang === 'ja' ? $architect['architectJa'] : $architect['architectEn']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-danger">建築家データがありません</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>建築物が見つかりませんでした。</p>
        <?php endif; ?>
    </div>
    
    <script>
        // Lucideアイコンの初期化
        lucide.createIcons();
    </script>
</body>
</html>
