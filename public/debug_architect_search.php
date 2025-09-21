<?php
// 建築家SLUG検索デバッグ用ファイル
require_once '../config/database.php';
require_once '../src/Services/BuildingService.php';
require_once '../src/Services/ArchitectService.php';

echo "<h2>建築家SLUG検索デバッグ</h2>";

try {
    $buildingService = new BuildingService();
    $architectService = new ArchitectService();
    
    $architectSlug = 'takenaka-corporation';
    echo "<h3>テストSLUG: {$architectSlug}</h3>";
    
    // 建築家情報を取得
    $architectInfo = $architectService->getBySlug($architectSlug, 'ja');
    echo "<h4>建築家情報</h4>";
    if ($architectInfo) {
        echo "建築家ID: " . $architectInfo['individual_architect_id'] . "<br>";
        echo "建築家名: " . $architectInfo['name_ja'] . "<br>";
    } else {
        echo "建築家情報が見つかりません<br>";
    }
    
    // 建築家の建築物を検索
    echo "<h4>建築物検索</h4>";
    $result = $buildingService->searchByArchitectSlug($architectSlug, 1, 'ja', 10);
    
    echo "総数: " . $result['total'] . "<br>";
    echo "建築物数: " . count($result['buildings']) . "<br>";
    
    if (count($result['buildings']) > 0) {
        echo "<h5>建築物一覧</h5>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>タイトル</th><th>建築家</th><th>場所</th></tr>";
        foreach ($result['buildings'] as $building) {
            echo "<tr>";
            echo "<td>" . $building['building_id'] . "</td>";
            echo "<td>" . htmlspecialchars($building['title']) . "</td>";
            echo "<td>" . htmlspecialchars($building['architects'][0]['architectJa'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($building['location']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "建築物が見つかりません<br>";
    }
    
    // 直接SQLで検索してみる
    echo "<h4>直接SQL検索</h4>";
    $db = getDB();
    
    // 建築家IDを取得
    $sql = "SELECT individual_architect_id FROM individual_architects_3 WHERE slug = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$architectSlug]);
    $architectRow = $stmt->fetch();
    
    if ($architectRow) {
        $architectId = $architectRow['individual_architect_id'];
        echo "建築家ID: {$architectId}<br>";
        
        // 建築物を検索
        $sql = "
            SELECT b.building_id, b.title, b.location,
                   ia.name_ja as architect_name
            FROM buildings_table_3 b
            LEFT JOIN building_architects ba ON b.building_id = ba.building_id
            LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
            LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
            WHERE ia.individual_architect_id = ?
            LIMIT 10
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$architectId]);
        $buildings = $stmt->fetchAll();
        
        echo "直接SQL検索結果: " . count($buildings) . "件<br>";
        if (count($buildings) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>タイトル</th><th>建築家</th><th>場所</th></tr>";
            foreach ($buildings as $building) {
                echo "<tr>";
                echo "<td>" . $building['building_id'] . "</td>";
                echo "<td>" . htmlspecialchars($building['title']) . "</td>";
                echo "<td>" . htmlspecialchars($building['architect_name']) . "</td>";
                echo "<td>" . htmlspecialchars($building['location']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
    echo "スタックトレース: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>デバッグ完了</h3>";
?>
