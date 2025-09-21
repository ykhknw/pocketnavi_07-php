<?php
// 距離計算デバッグ用ファイル
require_once '../config/database.php';
require_once '../src/Services/BuildingService.php';

echo "<h2>距離計算デバッグ</h2>";

try {
    $buildingService = new BuildingService();
    
    // テスト地点（名古屋駅周辺）
    $userLat = 35.1722;
    $userLng = 136.974;
    $radiusKm = 5;
    
    echo "<h3>テスト地点</h3>";
    echo "緯度: {$userLat}<br>";
    echo "経度: {$userLng}<br>";
    echo "半径: {$radiusKm}km<br>";
    
    // 位置検索を実行
    echo "<h3>位置検索実行</h3>";
    try {
        $result = $buildingService->searchByLocation($userLat, $userLng, $radiusKm, 1, false, false, 'ja', 10);
        
        echo "<h4>検索結果</h4>";
        echo "総数: " . $result['total'] . "<br>";
        echo "建築物数: " . count($result['buildings']) . "<br>";
    } catch (Exception $e) {
        echo "<h4>エラー発生</h4>";
        echo "エラーメッセージ: " . $e->getMessage() . "<br>";
        echo "スタックトレース: <pre>" . $e->getTraceAsString() . "</pre>";
        return;
    }
    
    if (count($result['buildings']) > 0) {
        echo "<h4>建築物一覧（距離順）</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>タイトル</th><th>場所</th><th>距離(km)</th><th>緯度</th><th>経度</th></tr>";
        foreach ($result['buildings'] as $building) {
            $distance = isset($building['distance']) ? round($building['distance'], 2) : 'N/A';
            echo "<tr>";
            echo "<td>" . $building['building_id'] . "</td>";
            echo "<td>" . htmlspecialchars($building['title']) . "</td>";
            echo "<td>" . htmlspecialchars($building['location']) . "</td>";
            echo "<td>" . $distance . "</td>";
            echo "<td>" . $building['lat'] . "</td>";
            echo "<td>" . $building['lng'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 手動で距離計算をテスト
        echo "<h4>手動距離計算テスト</h4>";
        $firstBuilding = $result['buildings'][0];
        $buildingLat = $firstBuilding['lat'];
        $buildingLng = $firstBuilding['lng'];
        
        // Haversine公式で距離を計算
        $earthRadius = 6371; // 地球の半径（km）
        $latDiff = deg2rad($buildingLat - $userLat);
        $lngDiff = deg2rad($buildingLng - $userLng);
        $a = sin($latDiff/2) * sin($latDiff/2) + 
             cos(deg2rad($userLat)) * cos(deg2rad($buildingLat)) * 
             sin($lngDiff/2) * sin($lngDiff/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $manualDistance = $earthRadius * $c;
        
        echo "建物: " . htmlspecialchars($firstBuilding['title']) . "<br>";
        echo "建物の緯度: {$buildingLat}<br>";
        echo "建物の経度: {$buildingLng}<br>";
        echo "手動計算距離: " . round($manualDistance, 2) . "km<br>";
        echo "データベース距離: " . round($firstBuilding['distance'], 2) . "km<br>";
        
    } else {
        echo "建築物が見つかりません<br>";
    }
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
    echo "スタックトレース: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>デバッグ完了</h3>";
?>
