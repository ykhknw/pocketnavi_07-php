<?php
// 都道府県データ確認用ファイル
require_once '../config/database.php';

echo "<h2>都道府県データ確認</h2>";

try {
    $db = getDB();
    
    // 都道府県データのサンプルを確認
    echo "<h3>都道府県データのサンプル（最初の20件）</h3>";
    $sql = "SELECT DISTINCT prefectures, prefecturesEn FROM buildings_table_3 WHERE prefectures IS NOT NULL AND prefectures != '' ORDER BY prefectures LIMIT 20";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>prefectures (日本語)</th><th>prefecturesEn (英語)</th></tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['prefectures']) . "</td>";
        echo "<td>" . htmlspecialchars($row['prefecturesEn']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 愛知県のデータを確認
    echo "<h3>愛知県のデータ</h3>";
    $sql = "SELECT prefectures, prefecturesEn, COUNT(*) as count FROM buildings_table_3 WHERE prefectures LIKE '%愛知%' OR prefecturesEn LIKE '%Aichi%' GROUP BY prefectures, prefecturesEn";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>prefectures (日本語)</th><th>prefecturesEn (英語)</th><th>件数</th></tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['prefectures']) . "</td>";
        echo "<td>" . htmlspecialchars($row['prefecturesEn']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 北海道のデータを確認
    echo "<h3>北海道のデータ</h3>";
    $sql = "SELECT prefectures, prefecturesEn, COUNT(*) as count FROM buildings_table_3 WHERE prefectures LIKE '%北海道%' OR prefecturesEn LIKE '%Hokkaido%' GROUP BY prefectures, prefecturesEn";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>prefectures (日本語)</th><th>prefecturesEn (英語)</th><th>件数</th></tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['prefectures']) . "</td>";
        echo "<td>" . htmlspecialchars($row['prefecturesEn']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
    echo "スタックトレース: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>デバッグ完了</h3>";
?>
