<?php
// 構文エラーチェック用テストファイル

echo "<h1>構文エラーチェック</h1>";

try {
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ functions.php の構文は正常です</p>";
    
    // 主要関数の存在確認
    $functions = [
        'searchBuildings',
        'searchBuildingsWithMultipleConditions', 
        'searchBuildingsByLocation',
        'searchBuildingsByArchitectSlug',
        'getBuildingBySlug',
        'getArchitectBySlug',
        'getPopularSearches'
    ];
    
    foreach ($functions as $function) {
        if (function_exists($function)) {
            echo "<p style='color: green;'>✓ $function() 関数存在</p>";
        } else {
            echo "<p style='color: red;'>✗ $function() 関数が見つかりません</p>";
        }
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
    
} catch (ParseError $e) {
    echo "<p style='color: red;'>✗ 構文エラー: " . $e->getMessage() . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>テスト完了</h2>";
echo "<p><a href='test_final_phase2.php'>最終テストに戻る</a></p>";
?>
