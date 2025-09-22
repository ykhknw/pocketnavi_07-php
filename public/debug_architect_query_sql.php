<?php
// 建築家ページでのキーワード検索SQLデバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定
$architectSlug = $_GET['architect_slug'] ?? 'inui-architects';
$query = $_GET['q'] ?? '美術館';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>建築家ページでのキーワード検索SQLデバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>query: " . htmlspecialchars($query) . "</p>";
echo "<p>lang: " . htmlspecialchars($lang) . "</p>";

$db = getDB();

echo "<h2>1. 建築家情報の確認</h2>";
$architectSql = "SELECT * FROM individual_architects_3 WHERE slug = ?";
$stmt = $db->prepare($architectSql);
$stmt->execute([$architectSlug]);
$architect = $stmt->fetch();
if ($architect) {
    echo "<p>建築家ID: " . $architect['individual_architect_id'] . "</p>";
    echo "<p>建築家名（日本語）: " . htmlspecialchars($architect['name_ja']) . "</p>";
    echo "<p>建築家名（英語）: " . htmlspecialchars($architect['name_en']) . "</p>";
} else {
    echo "<p>建築家が見つかりませんでした。</p>";
    exit;
}

echo "<h2>2. 建築家の建築物一覧（全件）</h2>";
$allBuildingsSql = "
    SELECT b.building_id, b.title, b.titleEn, b.buildingTypes, b.buildingTypesEn
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ?
    ORDER BY b.building_id DESC
";
$stmt = $db->prepare($allBuildingsSql);
$stmt->execute([$architectSlug]);
$allBuildings = $stmt->fetchAll();
echo "<p>建築家の建築物総数: " . count($allBuildings) . "件</p>";

echo "<h3>建築物一覧</h3>";
foreach ($allBuildings as $index => $building) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
    echo "<p>ID: " . $building['building_id'] . " - " . htmlspecialchars($building['title']) . "</p>";
    echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
    echo "</div>";
}

echo "<h2>3. キーワード「" . htmlspecialchars($query) . "」でのフィルタリング結果</h2>";

// PHP関数での検索結果を取得
$result = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, '', '', $query);

echo "<p>PHP関数での検索結果件数: " . $result['total'] . "件</p>";
echo "<p>totalPages: " . $result['totalPages'] . "</p>";

if (!empty($result['buildings'])) {
    echo "<h3>検索結果詳細</h3>";
    foreach ($result['buildings'] as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "<p>建築家: " . htmlspecialchars(implode(', ', array_column($building['architects'], 'architectJa'))) . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>検索結果がありません。</p>";
}

echo "<h2>4. 手動でのキーワードフィルタリング検証</h2>";
$queryLower = mb_strtolower($query);
$filteredCount = 0;
$filteredBuildings = [];

foreach ($allBuildings as $building) {
    $include = false;
    
    // タイトル検索
    if (mb_strpos(mb_strtolower($building['title']), $queryLower) !== false) {
        $include = true;
    }
    
    // 英語タイトル検索
    if (mb_strpos(mb_strtolower($building['titleEn']), $queryLower) !== false) {
        $include = true;
    }
    
    // 用途検索
    if (mb_strpos(mb_strtolower($building['buildingTypes']), $queryLower) !== false) {
        $include = true;
    }
    
    // 英語用途検索
    if (mb_strpos(mb_strtolower($building['buildingTypesEn']), $queryLower) !== false) {
        $include = true;
    }
    
    if ($include) {
        $filteredBuildings[] = $building;
        $filteredCount++;
    }
}

echo "<p>手動フィルタリング結果: " . $filteredCount . "件</p>";

if (!empty($filteredBuildings)) {
    echo "<h3>手動フィルタリング結果詳細</h3>";
    foreach ($filteredBuildings as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
        echo "</div>";
    }
}

echo "<h2>5. 比較結果</h2>";
echo "<p>建築家の建築物総数: " . count($allBuildings) . "件</p>";
echo "<p>手動フィルタリング: " . $filteredCount . "件</p>";
echo "<p>PHP関数検索: " . $result['total'] . "件</p>";

if ($filteredCount === $result['total']) {
    echo "<p style='color: green;'>✅ 一致: フィルタリング結果は正しく動作しています。</p>";
} else {
    echo "<p style='color: red;'>⚠️ 不一致: フィルタリング結果に問題があります。</p>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
</style>
