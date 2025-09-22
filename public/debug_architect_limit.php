<?php
// 建築家検索の件数制限デバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定
$architectSlug = $_GET['architect_slug'] ?? 'nikken-sekkei';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>建築家検索の件数制限デバッグ</h1>";
echo "<h2>パラメータ</h2>";
echo "<p>architectSlug: " . htmlspecialchars($architectSlug) . "</p>";
echo "<p>lang: " . htmlspecialchars($lang) . "</p>";
echo "<p>page: " . $page . "</p>";
echo "<p>limit: " . $limit . "</p>";

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

echo "<h2>2. データベース直接検索（全件）</h2>";
$allBuildingsSql = "
    SELECT COUNT(DISTINCT b.building_id) as total_count
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ?
";
$stmt = $db->prepare($allBuildingsSql);
$stmt->execute([$architectSlug]);
$totalCount = $stmt->fetchColumn();
echo "<p>データベース全体の件数: " . $totalCount . "件</p>";

echo "<h2>3. データベース直接検索（最初の20件）</h2>";
$buildingsSql = "
    SELECT b.building_id, b.title, b.titleEn, b.buildingTypes, b.buildingTypesEn
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ?
    ORDER BY b.building_id DESC
    LIMIT 20
";
$stmt = $db->prepare($buildingsSql);
$stmt->execute([$architectSlug]);
$buildings = $stmt->fetchAll();

echo "<p>データベースから取得した建築物数: " . count($buildings) . "件</p>";
foreach ($buildings as $index => $building) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
    echo "<p>順位: " . ($index + 1) . "</p>";
    echo "<p>ID: " . $building['building_id'] . "</p>";
    echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
    echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
    echo "</div>";
}

echo "<h2>4. PHP関数での検索結果（limit=10）</h2>";
$result1 = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit);
echo "<p>PHP関数での検索結果件数: " . $result1['total'] . "件</p>";
echo "<p>totalPages: " . $result1['totalPages'] . "</p>";

if (!empty($result1['buildings'])) {
    echo "<h3>PHP関数での検索結果詳細</h3>";
    foreach ($result1['buildings'] as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "</div>";
    }
}

echo "<h2>5. PHP関数での検索結果（limit=20）</h2>";
$result2 = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, 20);
echo "<p>PHP関数での検索結果件数: " . $result2['total'] . "件</p>";
echo "<p>totalPages: " . $result2['totalPages'] . "</p>";

if (!empty($result2['buildings'])) {
    echo "<h3>PHP関数での検索結果詳細（20件）</h3>";
    foreach ($result2['buildings'] as $index => $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>順位: " . ($index + 1) . "</p>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "</div>";
    }
}

echo "<h2>6. 比較結果</h2>";
echo "<p>データベース全体: " . $totalCount . "件</p>";
echo "<p>PHP関数（limit=10）: " . $result1['total'] . "件</p>";
echo "<p>PHP関数（limit=20）: " . $result2['total'] . "件</p>";

if ($totalCount > $result1['total']) {
    echo "<p style='color: red;'>⚠️ 問題発見: データベースでは" . $totalCount . "件あるのに、PHP関数では" . $result1['total'] . "件しか取得できていません。</p>";
} else {
    echo "<p style='color: green;'>✅ 正常: 件数は一致しています。</p>";
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
</style>
