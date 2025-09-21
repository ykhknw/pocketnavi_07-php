<?php
// 修正後の動作確認テスト

echo "<h1>修正後の動作確認テスト</h1>";

try {
    // データベース設定を読み込み
    require_once '../config/database.php';
    echo "<p style='color: green;'>✓ config/database.php 読み込み成功</p>";
    
    // 関数ファイルを読み込み
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ functions.php 読み込み成功</p>";
    
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
    
    // クラスの存在確認
    $classes = ['BuildingService', 'ArchitectService', 'ErrorHandler'];
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "<p style='color: green;'>✓ $class クラス存在</p>";
        } else {
            echo "<p style='color: red;'>✗ $class クラスが見つかりません</p>";
        }
    }
    
    // 検索機能のテスト
    echo "<h2>検索機能のテスト</h2>";
    try {
        $result = searchBuildings('東京', 1, false, false, 'ja', 5);
        if (isset($result['buildings']) && is_array($result['buildings'])) {
            echo "<p style='color: green;'>✓ 検索機能動作確認 - " . count($result['buildings']) . "件の結果</p>";
        } else {
            echo "<p style='color: red;'>✗ 検索機能エラー</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 検索機能エラー: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>テスト完了</h2>";
echo "<p><a href='index.php'>メインページに戻る</a></p>";
?>
