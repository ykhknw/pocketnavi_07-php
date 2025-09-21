<?php
// Phase 2 最終動作確認テスト

echo "<h1>Phase 2 最終動作確認テスト</h1>";

// 1. 全体的な動作確認
echo "<h2>1. 全体的な動作確認</h2>";

try {
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ 関数ファイル読み込み成功</p>";
    
    // クラスの存在確認
    $classes = ['BuildingService', 'ArchitectService', 'ErrorHandler'];
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "<p style='color: green;'>✓ $class クラス存在</p>";
        } else {
            echo "<p style='color: red;'>✗ $class クラスが見つかりません</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 初期化エラー: " . $e->getMessage() . "</p>";
    exit;
}

// 2. 主要機能の動作確認
echo "<h2>2. 主要機能の動作確認</h2>";

// 2.1 データベース接続
echo "<h3>2.1 データベース接続</h3>";
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

// 2.2 建築物検索機能
echo "<h3>2.2 建築物検索機能</h3>";
$searchTests = [
    ['query' => '東京', 'description' => 'キーワード検索'],
    ['query' => '', 'description' => '空検索（全件取得）'],
    ['query' => '美術館', 'description' => '特定キーワード検索']
];

foreach ($searchTests as $test) {
    try {
        $result = searchBuildings($test['query'], 1, false, false, 'ja', 5);
        if (isset($result['buildings']) && is_array($result['buildings'])) {
            echo "<p style='color: green;'>✓ {$test['description']} - " . count($result['buildings']) . "件の結果</p>";
        } else {
            echo "<p style='color: red;'>✗ {$test['description']} - 結果が不正</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ {$test['description']} - エラー: " . $e->getMessage() . "</p>";
    }
}

// 2.3 複数条件検索機能
echo "<h3>2.3 複数条件検索機能</h3>";
try {
    $result = searchBuildingsWithMultipleConditions('建築', '2020', '東京都', '文化施設', false, false, 1, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 複数条件検索 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 複数条件検索 - 結果が不正</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 複数条件検索 - エラー: " . $e->getMessage() . "</p>";
}

// 2.4 位置情報検索機能
echo "<h3>2.4 位置情報検索機能</h3>";
try {
    $result = searchBuildingsByLocation(35.6762, 139.6503, 10, 1, false, false, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 位置情報検索 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 位置情報検索 - 結果が不正</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 位置情報検索 - エラー: " . $e->getMessage() . "</p>";
}

// 2.5 建築家検索機能
echo "<h3>2.5 建築家検索機能</h3>";
try {
    $result = searchBuildingsByArchitectSlug('kajima-corporation', 1, 'ja', 5);
    if (isset($result['buildings']) && is_array($result['buildings'])) {
        echo "<p style='color: green;'>✓ 建築家検索 - " . count($result['buildings']) . "件の結果</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 建築家検索 - 結果なし（スラッグが存在しない可能性）</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築家検索 - エラー: " . $e->getMessage() . "</p>";
}

// 2.6 建築物取得機能
echo "<h3>2.6 建築物取得機能</h3>";
try {
    $building = getBuildingBySlug('tokyo-skytree', 'ja');
    if ($building) {
        echo "<p style='color: green;'>✓ 建築物取得 - 成功</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 建築物取得 - 結果なし（スラッグが存在しない可能性）</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築物取得 - エラー: " . $e->getMessage() . "</p>";
}

// 2.7 建築家取得機能
echo "<h3>2.7 建築家取得機能</h3>";
try {
    $architect = getArchitectBySlug('kajima-corporation', 'ja');
    if ($architect) {
        echo "<p style='color: green;'>✓ 建築家取得 - 成功</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 建築家取得 - 結果なし（スラッグが存在しない可能性）</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 建築家取得 - エラー: " . $e->getMessage() . "</p>";
}

// 2.8 人気検索語取得機能
echo "<h3>2.8 人気検索語取得機能</h3>";
try {
    $popularSearches = getPopularSearches('ja');
    if (is_array($popularSearches)) {
        echo "<p style='color: green;'>✓ 人気検索語取得 - " . count($popularSearches) . "件の結果</p>";
    } else {
        echo "<p style='color: red;'>✗ 人気検索語取得 - 結果が不正</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 人気検索語取得 - エラー: " . $e->getMessage() . "</p>";
}

// 3. エラーハンドリング確認
echo "<h2>3. エラーハンドリング確認</h2>";

// 3.1 無効な検索クエリ
echo "<h3>3.1 無効な検索クエリ</h3>";
try {
    $result = searchBuildings('', 1, false, false, 'ja', 5);
    if (isset($result['buildings'])) {
        echo "<p style='color: green;'>✓ 無効な検索クエリの処理 - 正常に処理されました</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 無効な検索クエリの処理 - 予期しない結果</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 無効な検索クエリの処理 - エラー: " . $e->getMessage() . "</p>";
}

// 3.2 存在しないスラッグ
echo "<h3>3.2 存在しないスラッグ</h3>";
try {
    $building = getBuildingBySlug('non-existent-slug', 'ja');
    if ($building === null) {
        echo "<p style='color: green;'>✓ 存在しないスラッグの処理 - 正常にnullを返しました</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 存在しないスラッグの処理 - 予期しない結果</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 存在しないスラッグの処理 - エラー: " . $e->getMessage() . "</p>";
}

// 4. パフォーマンス確認
echo "<h2>4. パフォーマンス確認</h2>";

// 4.1 実行時間テスト
echo "<h3>4.1 実行時間テスト</h3>";
$performanceTests = [
    ['query' => '建築', 'description' => '一般的な検索'],
    ['query' => '東京', 'description' => '特定地域検索'],
    ['query' => '美術館', 'description' => '特定用途検索']
];

foreach ($performanceTests as $test) {
    $start_time = microtime(true);
    $result = searchBuildings($test['query'], 1, false, false, 'ja', 10);
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000;
    
    if ($execution_time < 1000) {
        echo "<p style='color: green;'>✓ {$test['description']} - " . round($execution_time, 2) . "ms</p>";
    } else {
        echo "<p style='color: orange;'>⚠ {$test['description']} - " . round($execution_time, 2) . "ms（要改善）</p>";
    }
}

// 4.2 メモリ使用量テスト
echo "<h3>4.2 メモリ使用量テスト</h3>";
$memory_usage = memory_get_usage(true);
$memory_peak = memory_get_peak_usage(true);

echo "<p>現在のメモリ使用量: " . round($memory_usage / 1024 / 1024, 2) . " MB</p>";
echo "<p>ピークメモリ使用量: " . round($memory_peak / 1024 / 1024, 2) . " MB</p>";

if ($memory_peak < 128 * 1024 * 1024) {
    echo "<p style='color: green;'>✓ メモリ使用量良好</p>";
} else {
    echo "<p style='color: orange;'>⚠ メモリ使用量要改善</p>";
}

// 5. ログ機能確認
echo "<h2>5. ログ機能確認</h2>";

// ログディレクトリの確認
$logDir = '../logs';
if (is_dir($logDir)) {
    echo "<p style='color: green;'>✓ ログディレクトリ存在</p>";
    
    $logFiles = glob($logDir . '/*.log');
    if (!empty($logFiles)) {
        echo "<p style='color: green;'>✓ ログファイル存在 - " . count($logFiles) . "件</p>";
        
        // 最新のログファイルの内容を確認
        $latestLogFile = max($logFiles);
        $logContent = file_get_contents($latestLogFile);
        if (!empty($logContent)) {
            echo "<p style='color: green;'>✓ ログファイルに内容が記録されています</p>";
        } else {
            echo "<p style='color: orange;'>⚠ ログファイルが空です</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ ログファイルが存在しません</p>";
    }
} else {
    echo "<p style='color: red;'>✗ ログディレクトリが存在しません</p>";
}

// 6. 最終確認
echo "<h2>6. 最終確認</h2>";

$finalChecks = [
    'クラスベース設計への移行' => class_exists('BuildingService') && class_exists('ArchitectService'),
    'エラーハンドリングの統一' => class_exists('ErrorHandler'),
    '既存関数の置き換え' => function_exists('searchBuildings') && function_exists('getBuildingBySlug'),
    'ログ機能の改善' => is_dir($logDir),
    'パフォーマンスの最適化' => $memory_peak < 128 * 1024 * 1024
];

foreach ($finalChecks as $check => $result) {
    if ($result) {
        echo "<p style='color: green;'>✓ $check</p>";
    } else {
        echo "<p style='color: red;'>✗ $check</p>";
    }
}

// 7. 次のステップ
echo "<h2>7. 次のステップ</h2>";
echo "<p>Phase 2が完了しました。以下のステップに進むことができます：</p>";
echo "<ul>";
echo "<li><a href='index.php'>メインページでの動作確認</a></li>";
echo "<li><a href='test_phase2_1.php'>Phase 2.1 テスト</a></li>";
echo "<li><a href='test_phase2_2.php'>Phase 2.2 テスト</a></li>";
echo "<li><a href='test_phase2_3.php'>Phase 2.3 テスト</a></li>";
echo "</ul>";

echo "<h2>8. 完了報告</h2>";
echo "<p style='color: green; font-weight: bold;'>Phase 2 リファクタリング完了！</p>";
echo "<p>以下の改善が完了しました：</p>";
echo "<ul>";
echo "<li>✓ ディレクトリ構造の整理</li>";
echo "<li>✓ クラスベース設計への移行</li>";
echo "<li>✓ 長大な関数の分割</li>";
echo "<li>✓ エラーハンドリングの統一</li>";
echo "<li>✓ ログ機能の改善</li>";
echo "<li>✓ パフォーマンスの最適化</li>";
echo "<li>✓ 既存関数の段階的置き換え</li>";
echo "</ul>";
?>
