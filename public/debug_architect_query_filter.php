<?php
// 建築家検索のq=事務所フィルタリングデバッグ
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// パラメータ設定
$architectSlug = 'shigeru-ban-architects-1';
$query = '事務所';
$lang = 'ja';
$page = 1;
$limit = 10;

echo "<h1>建築家検索のq=事務所フィルタリングデバッグ</h1>";
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

echo "<h2>2. 建築家に関連する建築物の確認</h2>";
$buildingsSql = "
    SELECT b.building_id, b.title, b.titleEn, b.buildingTypes, b.buildingTypesEn
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ?
    ORDER BY b.building_id DESC
    LIMIT 10
";
$stmt = $db->prepare($buildingsSql);
$stmt->execute([$architectSlug]);
$buildings = $stmt->fetchAll();

echo "<p>建築家に関連する建築物数: " . count($buildings) . "件</p>";
foreach ($buildings as $building) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
    echo "<p>ID: " . $building['building_id'] . "</p>";
    echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
    echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
    echo "<p>用途（英語）: " . htmlspecialchars($building['buildingTypesEn']) . "</p>";
    echo "</div>";
}

echo "<h2>3. 事務所を含む建築物の確認</h2>";
$officeSql = "
    SELECT b.building_id, b.title, b.titleEn, b.buildingTypes, b.buildingTypesEn
    FROM buildings_table_3 b
    INNER JOIN building_architects ba ON b.building_id = ba.building_id
    INNER JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
    INNER JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
    WHERE ia.slug = ? 
    AND (b.buildingTypes LIKE ? OR b.buildingTypesEn LIKE ?)
    ORDER BY b.building_id DESC
    LIMIT 10
";
$stmt = $db->prepare($officeSql);
$stmt->execute([$architectSlug, '%事務所%', '%Office%']);
$officeBuildings = $stmt->fetchAll();

echo "<p>事務所を含む建築物数: " . count($officeBuildings) . "件</p>";
foreach ($officeBuildings as $building) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
    echo "<p>ID: " . $building['building_id'] . "</p>";
    echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
    echo "<p>用途: " . htmlspecialchars($building['buildingTypes']) . "</p>";
    echo "<p>用途（英語）: " . htmlspecialchars($building['buildingTypesEn']) . "</p>";
    echo "</div>";
}

echo "<h2>4. 修正前の関数での検索結果</h2>";
$result1 = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit);
echo "<p>検索結果件数: " . $result1['total'] . "件</p>";

echo "<h2>5. 修正後の関数での検索結果（q=事務所）</h2>";
$result2 = searchBuildingsByArchitectSlug($architectSlug, $page, $lang, $limit, '', '', $query);
echo "<p>検索結果件数: " . $result2['total'] . "件</p>";

if (!empty($result2['buildings'])) {
    echo "<h3>検索結果詳細</h3>";
    foreach ($result2['buildings'] as $building) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "<p>ID: " . $building['building_id'] . "</p>";
        echo "<p>タイトル: " . htmlspecialchars($building['title']) . "</p>";
        echo "<p>用途: " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
        echo "</div>";
    }
}

echo "<h2>6. デバッグ情報</h2>";
echo "<h3>修正前の結果（最初の5件）</h3>";
if (!empty($result1['buildings'])) {
    foreach (array_slice($result1['buildings'], 0, 5) as $building) {
        echo "<p>" . htmlspecialchars($building['title']) . " - " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
    }
}

echo "<h3>修正後の結果（最初の5件）</h3>";
if (!empty($result2['buildings'])) {
    foreach (array_slice($result2['buildings'], 0, 5) as $building) {
        echo "<p>" . htmlspecialchars($building['title']) . " - " . htmlspecialchars(implode(', ', $building['buildingTypes'])) . "</p>";
    }
}

?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; margin: 2rem; }
h1, h2, h3 { color: #333; }
</style>
