<?php
// ログ機能テスト

echo "<h1>ログ機能テスト</h1>";

try {
    require_once '../src/Views/includes/functions.php';
    echo "<p style='color: green;'>✓ 関数ファイル読み込み成功</p>";
    
    // ErrorHandlerクラスの存在確認
    if (class_exists('ErrorHandler')) {
        echo "<p style='color: green;'>✓ ErrorHandlerクラス存在</p>";
        
        // ログディレクトリの確認
        $logDir = '../logs';
        echo "<p>ログディレクトリパス: $logDir</p>";
        
        if (is_dir($logDir)) {
            echo "<p style='color: green;'>✓ ログディレクトリ存在</p>";
        } else {
            echo "<p style='color: orange;'>⚠ ログディレクトリが存在しません - 作成を試行します</p>";
            
            if (mkdir($logDir, 0755, true)) {
                echo "<p style='color: green;'>✓ ログディレクトリ作成成功</p>";
            } else {
                echo "<p style='color: red;'>✗ ログディレクトリ作成失敗</p>";
            }
        }
        
        // ログ機能のテスト
        echo "<h2>ログ機能のテスト</h2>";
        
        try {
            ErrorHandler::log("テストログメッセージ", ErrorHandler::LOG_LEVEL_INFO, [
                'test' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            echo "<p style='color: green;'>✓ ログ記録成功</p>";
            
            // ログファイルの確認
            $logFiles = glob($logDir . '/*.log');
            if (!empty($logFiles)) {
                echo "<p style='color: green;'>✓ ログファイル存在 - " . count($logFiles) . "件</p>";
                
                $latestLogFile = max($logFiles);
                echo "<p>最新ログファイル: " . basename($latestLogFile) . "</p>";
                
                $logContent = file_get_contents($latestLogFile);
                if (!empty($logContent)) {
                    echo "<p style='color: green;'>✓ ログファイルに内容が記録されています</p>";
                    echo "<p>ログ内容（最後の3行）:</p>";
                    $lines = explode("\n", trim($logContent));
                    $lastLines = array_slice($lines, -3);
                    foreach ($lastLines as $line) {
                        if (!empty($line)) {
                            echo "<p style='font-family: monospace; font-size: 12px;'>" . htmlspecialchars($line) . "</p>";
                        }
                    }
                } else {
                    echo "<p style='color: orange;'>⚠ ログファイルが空です</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ ログファイルが存在しません</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ ログ記録エラー: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ ErrorHandlerクラスが見つかりません</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
}

echo "<h2>テスト完了</h2>";
echo "<p><a href='test_final_phase2.php'>最終テストに戻る</a></p>";
?>
