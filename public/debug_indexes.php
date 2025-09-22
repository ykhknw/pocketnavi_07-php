<?php
// データベースインデックス分析
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';

try {
    $db = getDB();
    
    echo "<h1>データベースインデックス分析</h1>";
    
    // buildings_table_3のインデックス情報を取得
    $sql = "SHOW INDEX FROM buildings_table_3";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $indexes = $stmt->fetchAll();
    
    echo "<h2>buildings_table_3 のインデックス</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>Non_unique</th><th>Key_name</th><th>Seq_in_index</th><th>Column_name</th><th>Cardinality</th></tr>";
    
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Table']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Non_unique']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Seq_in_index']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Cardinality']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // テーブルサイズ情報
    echo "<h2>テーブルサイズ情報</h2>";
    $sizeSql = "
        SELECT 
            table_name,
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
            table_rows
        FROM information_schema.tables 
        WHERE table_schema = '_shinkenchiku_db' 
        AND table_name LIKE '%buildings%'
        ORDER BY (data_length + index_length) DESC
    ";
    
    $stmt = $db->prepare($sizeSql);
    $stmt->execute();
    $sizes = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>Size (MB)</th><th>Rows</th></tr>";
    foreach ($sizes as $size) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($size['table_name']) . "</td>";
        echo "<td>" . htmlspecialchars($size['Size (MB)']) . "</td>";
        echo "<td>" . htmlspecialchars($size['table_rows']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // よく使われるクエリのEXPLAIN分析
    echo "<h2>主要クエリのEXPLAIN分析</h2>";
    
    // 1. 建築物検索クエリ
    echo "<h3>建築物検索クエリ（座標検索）</h3>";
    $explainSql = "
        EXPLAIN SELECT b.building_id, b.title, b.lat, b.lng, b.location
        FROM buildings_table_3 b
        WHERE b.lat IS NOT NULL AND b.lng IS NOT NULL
        AND (6371 * acos(cos(radians(35.14961)) * cos(radians(b.lat)) * 
             cos(radians(b.lng) - radians(137.035537)) + 
             sin(radians(35.14961)) * sin(radians(b.lat)))) <= 5
        ORDER BY b.has_photo DESC, b.building_id DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($explainSql);
    $stmt->execute();
    $explain = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>id</th><th>select_type</th><th>table</th><th>type</th><th>possible_keys</th><th>key</th><th>key_len</th><th>ref</th><th>rows</th><th>Extra</th></tr>";
    foreach ($explain as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['select_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['table']) . "</td>";
        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['possible_keys']) . "</td>";
        echo "<td>" . htmlspecialchars($row['key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['key_len']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ref']) . "</td>";
        echo "<td>" . htmlspecialchars($row['rows']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
