<?php
// クイックテスト - getDB関数の動作確認

echo "<h1>クイックテスト - getDB関数の動作確認</h1>";

try {
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ 関数ファイル読み込み成功</p>";
    
    // getDB関数の存在確認
    if (function_exists('getDB')) {
        echo "<p style='color: green;'>✓ getDB() 関数存在</p>";
        
        // データベース接続テスト
        try {
            $db = getDB();
            if ($db) {
                echo "<p style='color: green;'>✓ データベース接続成功</p>";
                
                // 簡単なクエリテスト
                $stmt = $db->query("SELECT COUNT(*) as count FROM buildings_table_3");
                $result = $stmt->fetch();
                echo "<p>建築物テーブルの件数: " . $result['count'] . "</p>";
                
            } else {
                echo "<p style='color: red;'>✗ データベース接続失敗</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ データベース接続エラー: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ getDB() 関数が見つかりません</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>テスト完了</h2>";
echo "<p><a href='test_final_phase2.php'>最終テストに戻る</a></p>";
?>
