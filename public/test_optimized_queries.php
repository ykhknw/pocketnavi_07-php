<?php
// 最適化されたクエリのパフォーマンステスト
require_once '../config/database.php';
require_once '../src/Services/OptimizedBuildingService.php';
require_once '../src/Services/BuildingService.php';

try {
    $db = getDB();
    
    echo "<h1>Phase 4.1.2: クエリ最適化パフォーマンステスト</h1>";
    
    // テスト用のパラメータ
    $userLat = 35.14961;
    $userLng = 137.035537;
    $radiusKm = 5;
    $query = "図書館";
    $architectSlug = "toyo-ito-associates-architects-1";
    
    echo "<h2>1. 位置情報検索の比較</h2>";
    
    // 従来のクエリ
    echo "<h3>従来のクエリ</h3>";
    $buildingService = new BuildingService($db);
    
    $startTime = microtime(true);
    $oldResult = $buildingService->searchByLocation($userLat, $userLng, $radiusKm, 1, false, false, 'ja', 10);
    $oldTime = (microtime(true) - $startTime) * 1000;
    
    echo "<p>実行時間: " . round($oldTime, 2) . "ms</p>";
    echo "<p>取得件数: " . count($oldResult['buildings']) . "件</p>";
    echo "<p>総件数: " . $oldResult['total'] . "件</p>";
    
    // 最適化されたクエリ
    echo "<h3>最適化されたクエリ</h3>";
    $optimizedService = new OptimizedBuildingService($db);
    
    $startTime = microtime(true);
    $newResult = $optimizedService->searchByLocationOptimized($userLat, $userLng, $radiusKm, 1, false, false, 'ja', 10);
    $newTime = (microtime(true) - $startTime) * 1000;
    
    echo "<p>実行時間: " . round($newTime, 2) . "ms</p>";
    echo "<p>取得件数: " . count($newResult['buildings']) . "件</p>";
    echo "<p>総件数: " . $newResult['total'] . "件</p>";
    
    // 改善率の計算
    $improvement = (($oldTime - $newTime) / $oldTime) * 100;
    echo "<p style='color: " . ($improvement > 0 ? 'green' : 'red') . ";'>改善率: " . round($improvement, 1) . "%</p>";
    
    echo "<h2>2. キーワード検索の比較</h2>";
    
    // 従来のクエリ
    echo "<h3>従来のクエリ</h3>";
    $startTime = microtime(true);
    $oldKeywordResult = $buildingService->searchWithMultipleConditions($query, null, null, null, false, false, 1, 'ja', 10);
    $oldKeywordTime = (microtime(true) - $startTime) * 1000;
    
    echo "<p>実行時間: " . round($oldKeywordTime, 2) . "ms</p>";
    echo "<p>取得件数: " . count($oldKeywordResult['buildings']) . "件</p>";
    echo "<p>総件数: " . $oldKeywordResult['total'] . "件</p>";
    
    // 最適化されたクエリ
    echo "<h3>最適化されたクエリ</h3>";
    $startTime = microtime(true);
    $newKeywordResult = $optimizedService->searchByKeywordsOptimized($query, 1, false, false, 'ja', 10);
    $newKeywordTime = (microtime(true) - $startTime) * 1000;
    
    echo "<p>実行時間: " . round($newKeywordTime, 2) . "ms</p>";
    echo "<p>取得件数: " . count($newKeywordResult['buildings']) . "件</p>";
    echo "<p>総件数: " . $newKeywordResult['total'] . "件</p>";
    
    // 改善率の計算
    $keywordImprovement = (($oldKeywordTime - $newKeywordTime) / $oldKeywordTime) * 100;
    echo "<p style='color: " . ($keywordImprovement > 0 ? 'green' : 'red') . ";'>改善率: " . round($keywordImprovement, 1) . "%</p>";
    
    echo "<h2>3. 建築家検索の比較</h2>";
    
    // 従来のクエリ
    echo "<h3>従来のクエリ</h3>";
    $startTime = microtime(true);
    $oldArchitectResult = $buildingService->searchByArchitectSlug($architectSlug, 1, 'ja', 10);
    $oldArchitectTime = (microtime(true) - $startTime) * 1000;
    
    echo "<p>実行時間: " . round($oldArchitectTime, 2) . "ms</p>";
    echo "<p>取得件数: " . count($oldArchitectResult['buildings']) . "件</p>";
    echo "<p>総件数: " . $oldArchitectResult['total'] . "件</p>";
    
    // 最適化されたクエリ
    echo "<h3>最適化されたクエリ</h3>";
    $startTime = microtime(true);
    $newArchitectResult = $optimizedService->searchByArchitectOptimized($architectSlug, 1, 'ja', 10);
    $newArchitectTime = (microtime(true) - $startTime) * 1000;
    
    echo "<p>実行時間: " . round($newArchitectTime, 2) . "ms</p>";
    echo "<p>取得件数: " . count($newArchitectResult['buildings']) . "件</p>";
    echo "<p>総件数: " . $newArchitectResult['total'] . "件</p>";
    
    // 改善率の計算
    $architectImprovement = (($oldArchitectTime - $newArchitectTime) / $oldArchitectTime) * 100;
    echo "<p style='color: " . ($architectImprovement > 0 ? 'green' : 'red') . ";'>改善率: " . round($architectImprovement, 1) . "%</p>";
    
    echo "<h2>4. 総合パフォーマンス改善</h2>";
    $totalOldTime = $oldTime + $oldKeywordTime + $oldArchitectTime;
    $totalNewTime = $newTime + $newKeywordTime + $newArchitectTime;
    $totalImprovement = (($totalOldTime - $totalNewTime) / $totalOldTime) * 100;
    
    echo "<p><strong>従来の総実行時間:</strong> " . round($totalOldTime, 2) . "ms</p>";
    echo "<p><strong>最適化後の総実行時間:</strong> " . round($totalNewTime, 2) . "ms</p>";
    echo "<p style='color: " . ($totalImprovement > 0 ? 'green' : 'red') . "; font-size: 18px;'><strong>総合改善率: " . round($totalImprovement, 1) . "%</strong></p>";
    
    echo "<h2>5. 最適化の詳細</h2>";
    echo "<ul>";
    echo "<li><strong>位置情報検索:</strong> ST_Distance_Sphere関数の使用、範囲フィルターの追加</li>";
    echo "<li><strong>キーワード検索:</strong> 不要なJOINの削除、インデックス活用</li>";
    echo "<li><strong>建築家検索:</strong> 必要なJOINのみ使用、インデックス活用</li>";
    echo "<li><strong>全体的:</strong> インデックス最適化による大幅なパフォーマンス向上</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
