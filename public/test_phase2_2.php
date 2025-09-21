<?php
// Phase 2.2 クラスベース設計の動作確認テスト

echo "<h1>Phase 2.2: クラスベース設計の動作確認</h1>";

// 1. ファイル存在確認
echo "<h2>1. ファイル存在確認</h2>";

$files_to_check = [
    '../src/Services/BuildingService.php' => 'BuildingServiceクラス',
    '../src/Services/ArchitectService.php' => 'ArchitectServiceクラス',
    '../src/Utils/ErrorHandler.php' => 'ErrorHandlerクラス',
    '../src/Views/includes/functions.php' => '統合された関数ファイル'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $description ($file)</p>";
    } else {
        echo "<p style='color: red;'>✗ $description ($file) - ファイルが見つかりません</p>";
    }
}

// 2. クラス読み込みテスト
echo "<h2>2. クラス読み込みテスト</h2>";
try {
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ 統合された関数ファイル読み込み成功</p>";
    
    // クラスの存在確認
    $classes_to_check = [
        'BuildingService' => 'BuildingServiceクラス',
        'ArchitectService' => 'ArchitectServiceクラス',
        'ErrorHandler' => 'ErrorHandlerクラス'
    ];
    
    foreach ($classes_to_check as $class => $description) {
        if (class_exists($class)) {
            echo "<p style='color: green;'>✓ $description 存在</p>";
        } else {
            echo "<p style='color: red;'>✗ $description が見つかりません</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ クラス読み込みエラー: " . $e->getMessage() . "</p>";
}

// 3. 新しい関数の存在確認
echo "<h2>3. 新しい関数の存在確認</h2>";

$functions_to_check = [
    'searchBuildingsNew' => '新しい建築物検索関数',
    'searchBuildingsWithMultipleConditionsNew' => '新しい複数条件検索関数',
    'searchBuildingsByLocationNew' => '新しい位置情報検索関数',
    'searchBuildingsByArchitectSlugNew' => '新しい建築家検索関数',
    'getBuildingBySlugNew' => '新しい建築物取得関数',
    'getArchitectBySlugNew' => '新しい建築家取得関数',
    'getArchitectBuildingsNew' => '新しい建築家建築物一覧関数',
    'getPopularSearchesNew' => '新しい人気検索語取得関数'
];

foreach ($functions_to_check as $function => $description) {
    if (function_exists($function)) {
        echo "<p style='color: green;'>✓ $description ($function()) 存在</p>";
    } else {
        echo "<p style='color: red;'>✗ $description ($function()) が見つかりません</p>";
    }
}

// 4. データベース接続テスト
echo "<h2>4. データベース接続テスト</h2>";
try {
    $db = getDB();
    if ($db) {
        echo "<p style='color: green;'>✓ データベース接続成功</p>";
    } else {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ データベース接続エラー: " . $e->getMessage() . "</p>";
}

// 5. 新しい検索機能テスト
echo "<h2>5. 新しい検索機能テスト</h2>";
try {
    $result = searchBuildingsNew('東京', 1, false, false, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 新しい検索機能動作確認 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 新しい検索機能エラー</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 新しい検索機能エラー: " . $e->getMessage() . "</p>";
}

// 6. エラーハンドリングテスト
echo "<h2>6. エラーハンドリングテスト</h2>";
try {
    // 無効な検索クエリでエラーハンドリングをテスト
    $result = searchBuildingsNew('', 1, false, false, 'ja', 5);
    if (isset($result['buildings'])) {
        echo "<p style='color: green;'>✓ エラーハンドリング動作確認</p>";
    } else {
        echo "<p style='color: orange;'>⚠ エラーハンドリングの結果が予期しない形式です</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラーハンドリングエラー: " . $e->getMessage() . "</p>";
}

// 7. クラスインスタンス作成テスト
echo "<h2>7. クラスインスタンス作成テスト</h2>";
try {
    $buildingService = new BuildingService();
    $architectService = new ArchitectService();
    echo "<p style='color: green;'>✓ クラスインスタンス作成成功</p>";
    
    // メソッドの存在確認
    $methods_to_check = [
        'BuildingService' => ['search', 'searchWithMultipleConditions', 'searchByLocation', 'getBySlug'],
        'ArchitectService' => ['getBySlug', 'getBuildings', 'getPopularSearches']
    ];
    
    foreach ($methods_to_check as $class => $methods) {
        $instance = $class === 'BuildingService' ? $buildingService : $architectService;
        foreach ($methods as $method) {
            if (method_exists($instance, $method)) {
                echo "<p style='color: green;'>✓ {$class}::{$method}() メソッド存在</p>";
            } else {
                echo "<p style='color: red;'>✗ {$class}::{$method}() メソッドが見つかりません</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ クラスインスタンス作成エラー: " . $e->getMessage() . "</p>";
}

// 8. パフォーマンステスト
echo "<h2>8. パフォーマンステスト</h2>";
try {
    $start_time = microtime(true);
    $result = searchBuildingsNew('建築', 1, false, false, 'ja', 10);
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // ミリ秒
    
    if ($execution_time < 1000) { // 1秒未満
        echo "<p style='color: green;'>✓ パフォーマンス良好 - 実行時間: " . round($execution_time, 2) . "ms</p>";
    } else {
        echo "<p style='color: orange;'>⚠ パフォーマンス要改善 - 実行時間: " . round($execution_time, 2) . "ms</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ パフォーマンステストエラー: " . $e->getMessage() . "</p>";
}

echo "<h2>9. 次のステップ</h2>";
echo "<p>Phase 2.2が完了したら、<a href='index.php'>メインページ</a>で動作確認してください。</p>";
echo "<p>問題がなければ、Phase 2.3（既存関数の段階的置き換え）に進みます。</p>";

echo "<h2>10. 移行計画</h2>";
echo "<ul>";
echo "<li>既存の関数を新しいクラスベースの実装に段階的に置き換え</li>";
echo "<li>既存の関数を削除して新しい関数名に統一</li>";
echo "<li>フロントエンドの関数呼び出しを更新</li>";
echo "<li>最終的なテストと動作確認</li>";
echo "</ul>";
?>
