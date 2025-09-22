<?php
// 分割された関数の動作確認テスト

require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';

echo "<h1>分割された関数の動作確認テスト</h1>";

// 1. searchBuildings()関数のテスト
echo "<h2>1. searchBuildings()関数のテスト</h2>";
try {
    $result = searchBuildings('東京', 1, false, false, 'ja', 5);
    echo "<p style='color: green;'>✓ searchBuildings()関数動作確認 - " . count($result['buildings']) . "件の結果</p>";
    echo "<p>総件数: " . $result['total'] . "</p>";
    echo "<p>ページ数: " . $result['totalPages'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ searchBuildings()関数エラー: " . $e->getMessage() . "</p>";
}

// 2. buildBuildingSearchWhereClause()関数のテスト
echo "<h2>2. buildBuildingSearchWhereClause()関数のテスト</h2>";
try {
    $whereData = buildBuildingSearchWhereClause('東京', false, false);
    echo "<p style='color: green;'>✓ buildBuildingSearchWhereClause()関数動作確認</p>";
    echo "<p>WHERE句: " . htmlspecialchars($whereData['whereSql']) . "</p>";
    echo "<p>パラメータ数: " . count($whereData['params']) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ buildBuildingSearchWhereClause()関数エラー: " . $e->getMessage() . "</p>";
}

// 3. executeBuildingSearch()関数のテスト
echo "<h2>3. executeBuildingSearch()関数のテスト</h2>";
try {
    $whereData = buildBuildingSearchWhereClause('東京', false, false);
    $result = executeBuildingSearch($whereData['whereSql'], $whereData['params'], 1, 5);
    echo "<p style='color: green;'>✓ executeBuildingSearch()関数動作確認 - " . count($result['rows']) . "件の結果</p>";
    echo "<p>総件数: " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ executeBuildingSearch()関数エラー: " . $e->getMessage() . "</p>";
}

// 4. searchBuildingsWithMultipleConditions()関数のテスト
echo "<h2>4. searchBuildingsWithMultipleConditions()関数のテスト</h2>";
try {
    $result = searchBuildingsWithMultipleConditions('東京', '2020', '東京都', '事務所', false, false, 1, 'ja', 5);
    echo "<p style='color: green;'>✓ searchBuildingsWithMultipleConditions()関数動作確認 - " . count($result['buildings']) . "件の結果</p>";
    echo "<p>総件数: " . $result['total'] . "</p>";
    echo "<p>ページ数: " . $result['totalPages'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ searchBuildingsWithMultipleConditions()関数エラー: " . $e->getMessage() . "</p>";
}

// 5. buildMultipleConditionsWhereClause()関数のテスト
echo "<h2>5. buildMultipleConditionsWhereClause()関数のテスト</h2>";
try {
    $whereData = buildMultipleConditionsWhereClause('東京', '2020', '東京都', '事務所', false, false);
    echo "<p style='color: green;'>✓ buildMultipleConditionsWhereClause()関数動作確認</p>";
    echo "<p>WHERE句: " . htmlspecialchars($whereData['whereSql']) . "</p>";
    echo "<p>パラメータ数: " . count($whereData['params']) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ buildMultipleConditionsWhereClause()関数エラー: " . $e->getMessage() . "</p>";
}

// 6. searchBuildingsByLocation()関数のテスト
echo "<h2>6. searchBuildingsByLocation()関数のテスト</h2>";
try {
    $result = searchBuildingsByLocation(35.1879, 137.0026, 5, 1, false, false, 'ja', 5);
    echo "<p style='color: green;'>✓ searchBuildingsByLocation()関数動作確認 - " . count($result['buildings']) . "件の結果</p>";
    echo "<p>総件数: " . $result['total'] . "</p>";
    echo "<p>ページ数: " . $result['totalPages'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ searchBuildingsByLocation()関数エラー: " . $e->getMessage() . "</p>";
}

// 7. buildLocationSearchWhereClause()関数のテスト
echo "<h2>7. buildLocationSearchWhereClause()関数のテスト</h2>";
try {
    $whereData = buildLocationSearchWhereClause(35.1879, 137.0026, 5, false, false);
    echo "<p style='color: green;'>✓ buildLocationSearchWhereClause()関数動作確認</p>";
    echo "<p>WHERE句: " . htmlspecialchars($whereData['whereSql']) . "</p>";
    echo "<p>パラメータ数: " . count($whereData['params']) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ buildLocationSearchWhereClause()関数エラー: " . $e->getMessage() . "</p>";
}

// 8. executeLocationSearch()関数のテスト
echo "<h2>8. executeLocationSearch()関数のテスト</h2>";
try {
    $whereData = buildLocationSearchWhereClause(35.1879, 137.0026, 5, false, false);
    $result = executeLocationSearch($whereData['whereSql'], $whereData['params'], 35.1879, 137.0026, 1, 5);
    echo "<p style='color: green;'>✓ executeLocationSearch()関数動作確認 - " . count($result['rows']) . "件の結果</p>";
    echo "<p>総件数: " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ executeLocationSearch()関数エラー: " . $e->getMessage() . "</p>";
}

// 9. searchBuildingsByArchitectSlug()関数のテスト
echo "<h2>9. searchBuildingsByArchitectSlug()関数のテスト</h2>";
try {
    $result = searchBuildingsByArchitectSlug('kajima-corporation', 1, 'ja', 5);
    echo "<p style='color: green;'>✓ searchBuildingsByArchitectSlug()関数動作確認 - " . count($result['buildings']) . "件の結果</p>";
    echo "<p>総件数: " . $result['total'] . "</p>";
    echo "<p>ページ数: " . $result['totalPages'] . "</p>";
    echo "<p>建築家情報: " . ($result['architectInfo'] ? '取得済み' : '未取得') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ searchBuildingsByArchitectSlug()関数エラー: " . $e->getMessage() . "</p>";
}

// 10. buildArchitectSearchWhereClause()関数のテスト
echo "<h2>10. buildArchitectSearchWhereClause()関数のテスト</h2>";
try {
    $whereData = buildArchitectSearchWhereClause('kajima-corporation');
    echo "<p style='color: green;'>✓ buildArchitectSearchWhereClause()関数動作確認</p>";
    echo "<p>WHERE句: " . htmlspecialchars($whereData['whereSql']) . "</p>";
    echo "<p>パラメータ数: " . count($whereData['params']) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ buildArchitectSearchWhereClause()関数エラー: " . $e->getMessage() . "</p>";
}

// 11. executeArchitectSearch()関数のテスト
echo "<h2>11. executeArchitectSearch()関数のテスト</h2>";
try {
    $whereData = buildArchitectSearchWhereClause('kajima-corporation');
    $result = executeArchitectSearch($whereData['whereSql'], $whereData['params'], 1, 5);
    echo "<p style='color: green;'>✓ executeArchitectSearch()関数動作確認 - " . count($result['rows']) . "件の結果</p>";
    echo "<p>総件数: " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ executeArchitectSearch()関数エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>12. Phase 2.2 完了</h2>";
echo "<p>すべての長大な関数の分割が完了しました！</p>";
?>
