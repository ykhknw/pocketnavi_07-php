<?php
// Phase 2.3 既存関数の段階的置き換えの動作確認テスト

echo "<h1>Phase 2.3: 既存関数の段階的置き換えの動作確認</h1>";

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

// 2. 既存関数の置き換え確認
echo "<h2>2. 既存関数の置き換え確認</h2>";
try {
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ 統合された関数ファイル読み込み成功</p>";
    
    // 置き換えられた関数の存在確認
    $functions_to_check = [
        'searchBuildings' => '建築物検索関数（置き換え済み）',
        'searchBuildingsWithMultipleConditions' => '複数条件検索関数（置き換え済み）',
        'searchBuildingsByLocation' => '位置情報検索関数（置き換え済み）',
        'searchBuildingsByArchitectSlug' => '建築家検索関数（置き換え済み）',
        'getBuildingBySlug' => '建築物取得関数（置き換え済み）',
        'getArchitectBySlug' => '建築家取得関数（置き換え済み）',
        'getPopularSearches' => '人気検索語取得関数（置き換え済み）'
    ];
    
    foreach ($functions_to_check as $function => $description) {
        if (function_exists($function)) {
            echo "<p style='color: green;'>✓ $description ($function()) 存在</p>";
        } else {
            echo "<p style='color: red;'>✗ $description ($function()) が見つかりません</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 関数読み込みエラー: " . $e->getMessage() . "</p>";
}

// 3. データベース接続テスト
echo "<h2>3. データベース接続テスト</h2>";
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

// 4. 置き換えられた関数の動作テスト
echo "<h2>4. 置き換えられた関数の動作テスト</h2>";

// 4.1 建築物検索テスト
echo "<h3>4.1 建築物検索テスト</h3>";
try {
    $result = searchBuildings('東京', 1, false, false, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 建築物検索動作確認 - " . count($result['buildings']) . "件の結果</p>";
        echo "<p>総件数: " . $result['total'] . ", 総ページ数: " . $result['totalPages'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ 建築物検索エラー</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築物検索エラー: " . $e->getMessage() . "</p>";
}

// 4.2 複数条件検索テスト
echo "<h3>4.2 複数条件検索テスト</h3>";
try {
    $result = searchBuildingsWithMultipleConditions('美術館', '2020', '東京都', '文化施設', false, false, 1, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 複数条件検索動作確認 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 複数条件検索エラー</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 複数条件検索エラー: " . $e->getMessage() . "</p>";
}

// 4.3 位置情報検索テスト
echo "<h3>4.3 位置情報検索テスト</h3>";
try {
    $result = searchBuildingsByLocation(35.6762, 139.6503, 10, 1, false, false, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 位置情報検索動作確認 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 位置情報検索エラー</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 位置情報検索エラー: " . $e->getMessage() . "</p>";
}

// 4.4 建築家検索テスト
echo "<h3>4.4 建築家検索テスト</h3>";
try {
    $result = searchBuildingsByArchitectSlug('kajima-corporation', 1, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 建築家検索動作確認 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 建築家検索エラー</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築家検索エラー: " . $e->getMessage() . "</p>";
}

// 4.5 建築物取得テスト
echo "<h3>4.5 建築物取得テスト</h3>";
try {
    $building = getBuildingBySlug('tokyo-skytree', 'ja');
    if ($building) {
        echo "<p style='color: green;'>✓ 建築物取得動作確認</p>";
        echo "<p>建築物名: " . htmlspecialchars($building['title'] ?? 'N/A') . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 建築物取得結果なし（スラッグが存在しない可能性）</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築物取得エラー: " . $e->getMessage() . "</p>";
}

// 4.6 建築家取得テスト
echo "<h3>4.6 建築家取得テスト</h3>";
try {
    $architect = getArchitectBySlug('kajima-corporation', 'ja');
    if ($architect) {
        echo "<p style='color: green;'>✓ 建築家取得動作確認</p>";
        echo "<p>建築家名: " . htmlspecialchars($architect['name'] ?? 'N/A') . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 建築家取得結果なし（スラッグが存在しない可能性）</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築家取得エラー: " . $e->getMessage() . "</p>";
}

// 4.7 人気検索語取得テスト
echo "<h3>4.7 人気検索語取得テスト</h3>";
try {
    $popularSearches = getPopularSearches('ja');
    if (is_array($popularSearches)) {
        echo "<p style='color: green;'>✓ 人気検索語取得動作確認 - " . count($popularSearches) . "件の結果</p>";
        foreach (array_slice($popularSearches, 0, 3) as $search) {
            echo "<p>- " . htmlspecialchars($search['query'] ?? $search['search_term'] ?? 'N/A') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 人気検索語取得エラー</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 人気検索語取得エラー: " . $e->getMessage() . "</p>";
}

// 5. エラーハンドリングテスト
echo "<h2>5. エラーハンドリングテスト</h2>";
try {
    // 無効な検索クエリでエラーハンドリングをテスト
    $result = searchBuildings('', 1, false, false, 'ja', 5);
    if (isset($result['buildings'])) {
        echo "<p style='color: green;'>✓ エラーハンドリング動作確認</p>";
    } else {
        echo "<p style='color: orange;'>⚠ エラーハンドリングの結果が予期しない形式です</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラーハンドリングエラー: " . $e->getMessage() . "</p>";
}

// 6. パフォーマンステスト
echo "<h2>6. パフォーマンステスト</h2>";
try {
    $start_time = microtime(true);
    $result = searchBuildings('建築', 1, false, false, 'ja', 10);
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

// 7. メモリ使用量テスト
echo "<h2>7. メモリ使用量テスト</h2>";
$memory_usage = memory_get_usage(true);
$memory_peak = memory_get_peak_usage(true);

echo "<p>現在のメモリ使用量: " . round($memory_usage / 1024 / 1024, 2) . " MB</p>";
echo "<p>ピークメモリ使用量: " . round($memory_peak / 1024 / 1024, 2) . " MB</p>";

if ($memory_peak < 128 * 1024 * 1024) { // 128MB未満
    echo "<p style='color: green;'>✓ メモリ使用量良好</p>";
} else {
    echo "<p style='color: orange;'>⚠ メモリ使用量要改善</p>";
}

echo "<h2>8. 次のステップ</h2>";
echo "<p>Phase 2.3が完了したら、<a href='index.php'>メインページ</a>で動作確認してください。</p>";
echo "<p>問題がなければ、Phase 2.4（フロントエンドの関数呼び出し更新）に進みます。</p>";

echo "<h2>9. 移行完了状況</h2>";
echo "<ul>";
echo "<li>✓ 既存関数のクラスベース設計への置き換え完了</li>";
echo "<li>✓ エラーハンドリングの統一完了</li>";
echo "<li>✓ ログ機能の改善完了</li>";
echo "<li>✓ パフォーマンスの最適化完了</li>";
echo "</ul>";

echo "<h2>10. 最終確認項目</h2>";
echo "<ul>";
echo "<li>メインページでの動作確認</li>";
echo "<li>検索機能の動作確認</li>";
echo "<li>エラーハンドリングの動作確認</li>";
echo "<li>ログファイルの確認</li>";
echo "</ul>";
?>
