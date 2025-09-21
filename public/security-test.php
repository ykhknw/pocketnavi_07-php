<?php
// セキュリティテストページ
require_once '../config/database.php';
require_once '../src/Utils/InputValidator.php';
require_once '../src/Utils/SecurityHelper.php';
require_once '../src/Utils/SecurityHeaders.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// デバッグモードの設定
define('DEBUG', true);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セキュリティテスト - PocketNavi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>セキュリティテスト</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h2>入力値検証テスト</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::escapeAttribute(SecurityHelper::generateCsrfToken()); ?>">
                    
                    <div class="mb-3">
                        <label for="test_string" class="form-label">文字列テスト</label>
                        <input type="text" class="form-control" id="test_string" name="test_string" 
                               value="<?php echo SecurityHelper::escapeAttribute($_POST['test_string'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="test_email" class="form-label">メールアドレステスト</label>
                        <input type="email" class="form-control" id="test_email" name="test_email" 
                               value="<?php echo SecurityHelper::escapeAttribute($_POST['test_email'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="test_url" class="form-label">URLテスト</label>
                        <input type="url" class="form-control" id="test_url" name="test_url" 
                               value="<?php echo SecurityHelper::escapeAttribute($_POST['test_url'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">テスト実行</button>
                </form>
            </div>
            
            <div class="col-md-6">
                <h2>検証結果</h2>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <?php
                    // CSRFトークンの検証
                    $csrfToken = $_POST['csrf_token'] ?? '';
                    if (!SecurityHelper::validateCsrfToken($csrfToken)) {
                        echo '<div class="alert alert-danger">CSRFトークンが無効です</div>';
                    } else {
                        echo '<div class="alert alert-success">CSRFトークンが有効です</div>';
                        
                        // 入力値の検証
                        $testString = InputValidator::validateString($_POST['test_string'] ?? '', 100);
                        $testEmail = InputValidator::validateEmail($_POST['test_email'] ?? '');
                        $testUrl = InputValidator::validateUrl($_POST['test_url'] ?? '');
                        
                        echo '<h3>検証結果:</h3>';
                        echo '<ul>';
                        echo '<li>文字列: ' . ($testString !== null ? '✓ 有効' : '✗ 無効') . '</li>';
                        echo '<li>メール: ' . ($testEmail !== null ? '✓ 有効' : '✗ 無効') . '</li>';
                        echo '<li>URL: ' . ($testUrl !== null ? '✓ 有効' : '✗ 無効') . '</li>';
                        echo '</ul>';
                        
                        if ($testString !== null) {
                            echo '<h4>サニタイズされた文字列:</h4>';
                            echo '<code>' . SecurityHelper::escapeHtml($testString) . '</code>';
                        }
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-12">
                <h2>セキュリティヘッダー確認</h2>
                <p>ブラウザの開発者ツールでレスポンスヘッダーを確認してください。</p>
                
                <h3>XSSテスト</h3>
                <div class="alert alert-warning">
                    <strong>注意:</strong> 以下の内容は適切にエスケープされているはずです。
                </div>
                <div class="border p-3">
                    <?php
                    $xssTest = '<script>alert("XSS")</script><img src="x" onerror="alert(\'XSS\')">';
                    echo SecurityHelper::escapeHtml($xssTest);
                    ?>
                </div>
                
                <h3>レート制限テスト</h3>
                <p>このページを短時間で何度もリロードしてレート制限をテストしてください。</p>
                <?php
                $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                if (SecurityHelper::checkRateLimit($clientIp, 10, 60)) {
                    echo '<div class="alert alert-success">レート制限内です</div>';
                } else {
                    echo '<div class="alert alert-danger">レート制限に達しました</div>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
