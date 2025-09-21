<?php
// 現在地検索のデバッグ用ファイル
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';

echo "<h2>現在地検索デバッグ</h2>";

// テスト用の座標（名古屋駅周辺）
$testLat = 35.14961;
$testLng = 137.035537;
$testRadius = 5;

echo "<h3>テストパラメータ</h3>";
echo "緯度: $testLat<br>";
echo "経度: $testLng<br>";
echo "半径: {$testRadius}km<br><br>";

// 1. データベースに座標データが存在するか確認
echo "<h3>1. データベースの座標データ確認</h3>";
try {
    $db = getDB();
    $sql = "SELECT building_id, title, lat, lng, location FROM buildings_table_3 WHERE lat IS NOT NULL AND lng IS NOT NULL AND lat != 0 AND lng != 0 LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    echo "座標データの件数: " . count($rows) . "<br>";
    if (count($rows) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>タイトル</th><th>緯度</th><th>経度</th><th>住所</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['building_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['lat']) . "</td>";
            echo "<td>" . htmlspecialchars($row['lng']) . "</td>";
            echo "<td>" . htmlspecialchars($row['location']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
}

// 2. 指定座標周辺のデータを直接確認
echo "<h3>2. 指定座標周辺のデータ確認</h3>";
try {
    $db = getDB();
    $sql = "SELECT building_id, title, lat, lng, location,
            (6371 * acos(
                cos(radians(?)) * cos(radians(lat)) * 
                cos(radians(lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(lat))
            )) as distance
            FROM buildings_table_3 
            WHERE lat IS NOT NULL AND lng IS NOT NULL AND lat != 0 AND lng != 0
            HAVING distance <= ?
            ORDER BY distance
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$testLat, $testLng, $testLat, $testRadius]);
    $rows = $stmt->fetchAll();
    
    echo "指定座標周辺のデータ件数: " . count($rows) . "<br>";
    if (count($rows) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>タイトル</th><th>緯度</th><th>経度</th><th>距離(km)</th><th>住所</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['building_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['lat']) . "</td>";
            echo "<td>" . htmlspecialchars($row['lng']) . "</td>";
            echo "<td>" . number_format($row['distance'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['location']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "指定座標周辺にデータが見つかりませんでした。<br>";
    }
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
}

// 3. BuildingServiceのsearchByLocationメソッドをテスト
echo "<h3>3. BuildingServiceのsearchByLocationメソッドテスト</h3>";
try {
    $buildingService = new BuildingService();
    $result = $buildingService->searchByLocation($testLat, $testLng, $testRadius, 1, false, false, 'ja', 10);
    
    echo "検索結果: <br>";
    echo "総件数: " . $result['total'] . "<br>";
    echo "建物数: " . count($result['buildings']) . "<br>";
    echo "総ページ数: " . $result['totalPages'] . "<br>";
    echo "現在のページ: " . $result['currentPage'] . "<br>";
    
    if (count($result['buildings']) > 0) {
        echo "<h4>検索結果の建物:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>タイトル</th><th>緯度</th><th>経度</th><th>住所</th></tr>";
        foreach ($result['buildings'] as $building) {
            if (is_array($building)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($building['building_id'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['title'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['lat'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['lng'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['location'] ?? '') . "</td>";
                echo "</tr>";
            } else {
                echo "<tr><td colspan='5'>Invalid building data: " . gettype($building) . "</td></tr>";
            }
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
}

// 4. searchBuildingsByLocation関数をテスト
echo "<h3>4. searchBuildingsByLocation関数テスト</h3>";
try {
    $result = searchBuildingsByLocation($testLat, $testLng, $testRadius, 1, false, false, 'ja', 10);
    
    echo "検索結果: <br>";
    echo "総件数: " . $result['total'] . "<br>";
    echo "建物数: " . count($result['buildings']) . "<br>";
    echo "総ページ数: " . $result['totalPages'] . "<br>";
    echo "現在のページ: " . $result['currentPage'] . "<br>";
    
    if (count($result['buildings']) > 0) {
        echo "<h4>検索結果の建物:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>タイトル</th><th>緯度</th><th>経度</th><th>住所</th></tr>";
        foreach ($result['buildings'] as $building) {
            if (is_array($building)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($building['building_id'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['title'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['lat'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['lng'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($building['location'] ?? '') . "</td>";
                echo "</tr>";
            } else {
                echo "<tr><td colspan='5'>Invalid building data: " . gettype($building) . "</td></tr>";
            }
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
}

echo "<h3>デバッグ完了</h3>";
?>