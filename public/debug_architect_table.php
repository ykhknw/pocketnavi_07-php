<?php
// 建築家テーブルの構造確認用ファイル
require_once '../config/database.php';

echo "<h2>建築家テーブル構造確認</h2>";

try {
    $db = getDB();
    
    // individual_architects_3テーブルの構造を確認
    echo "<h3>individual_architects_3テーブルの構造</h3>";
    $sql = "DESCRIBE individual_architects_3";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th><th>追加</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // サンプルデータを確認
    echo "<h3>サンプルデータ（最初の5件）</h3>";
    $sql = "SELECT * FROM individual_architects_3 LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    if (count($rows) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach (array_keys($rows[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 建築家名で検索してみる
    echo "<h3>建築家名での検索テスト</h3>";
    $sql = "SELECT * FROM individual_architects_3 WHERE name_ja LIKE '%竹中%' OR name_en LIKE '%takenaka%' LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    echo "検索結果: " . count($rows) . "件<br>";
    if (count($rows) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach (array_keys($rows[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
}

echo "<h3>デバッグ完了</h3>";
?>
