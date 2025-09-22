<?php
// 建築家ページのバッジURL生成テスト
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// 言語設定
$lang = InputValidator::validateLanguage($_GET['lang'] ?? 'ja');

// 建築家スラッグを取得
$architectSlug = $_GET['architect_slug'] ?? 'act-planning-1';

// 検索結果の取得（実際のindex.phpと同じ処理）
$searchResult = searchBuildingsByArchitectSlug($architectSlug, 1, $lang, 5);
$buildings = $searchResult['buildings'] ?? [];
$total = $searchResult['total'] ?? 0;
$architectInfo = $searchResult['architectInfo'] ?? null;

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>建築家ページバッジURL生成テスト - <?php echo htmlspecialchars($architectSlug); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
        <h1>建築家ページバッジURL生成テスト</h1>
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
                        
                        <!-- 建築家バッジの表示 -->
                        <?php if (!empty($building['architects'])): ?>
                            <div class="card-text mb-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($building['architects'] as $architect): ?>
                                        <a href="/architects/<?php echo urlencode($architect['slug']); ?>/?lang=<?php echo $lang; ?>" 
                                           class="architect-badge text-decoration-none">
                                            <i data-lucide="circle-user-round" class="me-1" style="width: 12px; height: 12px;"></i>
                                            <?php echo htmlspecialchars($lang === 'ja' ? $architect['architectJa'] : $architect['architectEn']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 建物用途バッジの表示 -->
                        <?php if (!empty($building['buildingTypes'])): ?>
                            <div class="card-text mb-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($building['buildingTypes'] as $type): ?>
                                        <?php 
                                        // 現在のページが建築家ページかどうかを判定
                                        $isArchitectPage = isset($_GET['architects_slug']) && !empty($_GET['architects_slug']);
                                        
                                        if ($isArchitectPage) {
                                            // 建築家ページの場合：既存のパラメータを保持してqパラメータを追加
                                            $architectSlug = $_GET['architects_slug'];
                                            $urlParams = ['q' => $type, 'lang' => $lang];
                                            
                                            // 既存のパラメータを保持
                                            if (isset($_GET['completionYears']) && !empty($_GET['completionYears'])) {
                                                $urlParams['completionYears'] = $_GET['completionYears'];
                                            }
                                            if (isset($_GET['prefectures']) && !empty($_GET['prefectures'])) {
                                                $urlParams['prefectures'] = $_GET['prefectures'];
                                            }
                                            if (isset($_GET['photos']) && !empty($_GET['photos'])) {
                                                $urlParams['photos'] = $_GET['photos'];
                                            }
                                            if (isset($_GET['videos']) && !empty($_GET['videos'])) {
                                                $urlParams['videos'] = $_GET['videos'];
                                            }
                                            
                                            $url = "/architects/{$architectSlug}/?" . http_build_query($urlParams);
                                        } else {
                                            // 通常ページの場合：既存のロジックを使用
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
                                            $url = "/index.php?" . http_build_query($urlParams);
                                        }
                                        ?>
                                        <a href="<?php echo $url; ?>" 
                                           class="building-type-badge text-decoration-none"
                                           title="<?php echo $lang === 'ja' ? 'この用途で検索' : 'Search by this building type'; ?>">
                                            <i data-lucide="building" class="me-1" style="width: 12px; height: 12px;"></i>
                                            <?php echo htmlspecialchars($type); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 都道府県バッジの表示 -->
                        <?php if (!empty($building['prefectures'])): ?>
                            <div class="card-text mb-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <?php 
                                    // 現在のページが建築家ページかどうかを判定
                                    $isArchitectPage = isset($_GET['architects_slug']) && !empty($_GET['architects_slug']);
                                    
                                    if ($isArchitectPage) {
                                        // 建築家ページの場合：既存のパラメータを保持してprefecturesパラメータを追加
                                        $architectSlug = $_GET['architects_slug'];
                                        $urlParams = ['prefectures' => $building['prefecturesEn'], 'lang' => $lang];
                                        
                                        // 既存のパラメータを保持
                                        if (isset($_GET['completionYears']) && !empty($_GET['completionYears'])) {
                                            $urlParams['completionYears'] = $_GET['completionYears'];
                                        }
                                        if (isset($_GET['photos']) && !empty($_GET['photos'])) {
                                            $urlParams['photos'] = $_GET['photos'];
                                        }
                                        if (isset($_GET['videos']) && !empty($_GET['videos'])) {
                                            $urlParams['videos'] = $_GET['videos'];
                                        }
                                        if (isset($_GET['q']) && !empty($_GET['q'])) {
                                            $urlParams['q'] = $_GET['q'];
                                        }
                                        
                                        $url = "/architects/{$architectSlug}/?" . http_build_query($urlParams);
                                    } else {
                                        // 通常ページの場合：既存のロジックを使用
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
                                        $url = "/index.php?" . http_build_query($urlParams);
                                    }
                                    ?>
                                    <a href="<?php echo $url; ?>" 
                                       class="prefecture-badge text-decoration-none">
                                        <i data-lucide="map-pin" class="me-1" style="width: 12px; height: 12px;"></i>
                                        <?php echo htmlspecialchars($lang === 'ja' ? $building['prefectures'] : $building['prefecturesEn']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 建築年バッジの表示 -->
                        <?php if ($building['completionYears']): ?>
                            <div class="card-text mb-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <?php 
                                    // 現在のページが建築家ページかどうかを判定
                                    $isArchitectPage = isset($_GET['architects_slug']) && !empty($_GET['architects_slug']);
                                    
                                    if ($isArchitectPage) {
                                        // 建築家ページの場合：既存のパラメータを保持してcompletionYearsパラメータを追加
                                        $architectSlug = $_GET['architects_slug'];
                                        $urlParams = ['completionYears' => $building['completionYears'], 'lang' => $lang];
                                        
                                        // 既存のパラメータを保持
                                        if (isset($_GET['prefectures']) && !empty($_GET['prefectures'])) {
                                            $urlParams['prefectures'] = $_GET['prefectures'];
                                        }
                                        if (isset($_GET['photos']) && !empty($_GET['photos'])) {
                                            $urlParams['photos'] = $_GET['photos'];
                                        }
                                        if (isset($_GET['videos']) && !empty($_GET['videos'])) {
                                            $urlParams['videos'] = $_GET['videos'];
                                        }
                                        if (isset($_GET['q']) && !empty($_GET['q'])) {
                                            $urlParams['q'] = $_GET['q'];
                                        }
                                        
                                        $url = "/architects/{$architectSlug}/?" . http_build_query($urlParams);
                                    } else {
                                        // 通常ページの場合：既存のロジックを使用
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
                                        $url = "/index.php?" . http_build_query($urlParams);
                                    }
                                    ?>
                                    <a href="<?php echo $url; ?>" 
                                       class="completion-year-badge text-decoration-none"
                                       title="<?php echo $lang === 'ja' ? 'この建築年で検索' : 'Search by this completion year'; ?>">
                                        <i data-lucide="calendar" class="me-1" style="width: 12px; height: 12px;"></i>
                                        <?php echo $building['completionYears']; ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- URL生成のデバッグ情報 -->
                        <div class="mt-3">
                            <h6>URL生成デバッグ情報:</h6>
                            <p><strong>現在のURL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
                            <p><strong>GET パラメータ:</strong></p>
                            <pre><?php print_r($_GET); ?></pre>
                        </div>
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
