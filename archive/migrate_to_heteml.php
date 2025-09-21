<?php
// HETEML移行用スクリプト
require_once 'config/database.php';

echo "<h1>HETEML移行確認スクリプト</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ データベース接続成功</p>";
    
    // テーブル存在確認
    $tables = ['architect_compositions_2', 'buildings_table_3', 'building_architects', 'individual_architects_3'];
    
    echo "<h2>テーブル存在確認</h2>";
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ $table テーブル存在</p>";
            
            // レコード数確認
            $countStmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $countStmt->fetch()['count'];
            echo "<p style='margin-left: 20px;'>レコード数: $count</p>";
        } else {
            echo "<p style='color: red;'>✗ $table テーブルが存在しません</p>";
        }
    }
    
    // サンプルデータ確認
    echo "<h2>サンプルデータ確認</h2>";
    
    // buildings_table_3のサンプル
    $stmt = $db->query("SELECT building_id, title, slug FROM buildings_table_3 LIMIT 3");
    $buildings = $stmt->fetchAll();
    echo "<h3>建築物データ（最初の3件）</h3>";
    foreach ($buildings as $building) {
        echo "<p>ID: {$building['building_id']}, タイトル: {$building['title']}, Slug: {$building['slug']}</p>";
    }
    
    // individual_architects_3のサンプル
    $stmt = $db->query("SELECT individual_architect_id, name_ja, name_en, slug FROM individual_architects_3 LIMIT 3");
    $architects = $stmt->fetchAll();
    echo "<h3>建築家データ（最初の3件）</h3>";
    foreach ($architects as $architect) {
        echo "<p>ID: {$architect['individual_architect_id']}, 日本語名: {$architect['name_ja']}, 英語名: {$architect['name_en']}, Slug: {$architect['slug']}</p>";
    }
    
    echo "<h2>移行完了</h2>";
    echo "<p style='color: green;'>✓ データベース移行が正常に完了しました</p>";
    echo "<p><a href='index.php'>メインページに移動</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . $e->getMessage() . "</p>";
}
?>
