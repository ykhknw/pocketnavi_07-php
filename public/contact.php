<?php
// PocketNavi - Contact Us Page
require_once '../config/database.php';
require_once '../src/Views/includes/functions.php';

// 言語設定（URLクエリパラメータから取得）
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['ja', 'en']) ? $_GET['lang'] : 'ja';

// ページタイトル
$pageTitle = $lang === 'ja' ? 'お問い合わせ' : 'Contact Us';

// フォーム送信処理
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message_content = trim($_POST['message'] ?? '');
    
    // バリデーション
    if (empty($name) || empty($email) || empty($message_content)) {
        $message = $lang === 'ja' ? 'すべての項目を入力してください。' : 'Please fill in all fields.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = $lang === 'ja' ? '有効なメールアドレスを入力してください。' : 'Please enter a valid email address.';
        $messageType = 'danger';
    } else {
        // メール送信処理（実際の実装では適切なメール送信ライブラリを使用）
        // ここでは簡単な例として、ログファイルに記録
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'name' => $name,
            'email' => $email,
            'message' => $message_content,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logFile = 'logs/contact_' . date('Y-m') . '.log';
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        
        $message = $lang === 'ja' ? 'お問い合わせを受け付けました。ありがとうございます。' : 'Thank you for your inquiry. We have received your message.';
        $messageType = 'success';
        
        // フォームをクリア
        $name = $email = $message_content = '';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'ja' ? 'ja' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - PocketNavi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Header -->
    <?php include '../src/Views/includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h1 class="h2 mb-4"><?php echo $pageTitle; ?></h1>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        <i data-lucide="user" class="me-1" style="width: 16px; height: 16px;"></i>
                                        <?php echo $lang === 'ja' ? 'お名前' : 'Your Name'; ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        <i data-lucide="mail" class="me-1" style="width: 16px; height: 16px;"></i>
                                        <?php echo $lang === 'ja' ? 'メールアドレス' : 'Email Address'; ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">
                                    <i data-lucide="message-square" class="me-1" style="width: 16px; height: 16px;"></i>
                                    <?php echo $lang === 'ja' ? 'お問い合わせ内容' : 'Inquiry Details'; ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="message" name="message" rows="6" 
                                          placeholder="<?php echo $lang === 'ja' ? 'お問い合わせ内容をご記入ください...' : 'Please enter your inquiry details...'; ?>" required><?php echo htmlspecialchars($message_content ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="send" class="me-2" style="width: 16px; height: 16px;"></i>
                                    <?php echo $lang === 'ja' ? '送信' : 'Send'; ?>
                                </button>
                                <button type="reset" class="btn btn-outline-secondary ms-2">
                                    <i data-lucide="refresh-cw" class="me-2" style="width: 16px; height: 16px;"></i>
                                    <?php echo $lang === 'ja' ? 'リセット' : 'Reset'; ?>
                                </button>
                            </div>
                        </form>
                        
                        
                        <!-- Back to Home -->
                        <div class="mt-4">
                            <a href="index.php?lang=<?php echo $lang; ?>" class="btn btn-outline-primary">
                                <i data-lucide="home" class="me-2" style="width: 16px; height: 16px;"></i>
                                <?php echo $lang === 'ja' ? 'ホームに戻る' : 'Back to Home'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../src/Views/includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        lucide.createIcons();
        
        // 言語切り替え機能
        function initLanguageSwitch() {
            const languageSwitch = document.getElementById('languageSwitch');
            if (languageSwitch) {
                languageSwitch.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // 現在のURLを取得
                    const currentUrl = new URL(window.location);
                    const currentLang = currentUrl.searchParams.get('lang') || 'ja';
                    
                    // 言語を切り替え
                    const newLang = currentLang === 'ja' ? 'en' : 'ja';
                    currentUrl.searchParams.set('lang', newLang);
                    
                    // ページをリロード
                    window.location.href = currentUrl.toString();
                });
            }
        }
        
        // ページ読み込み時の初期化
        document.addEventListener('DOMContentLoaded', function() {
            initLanguageSwitch();
        });
    </script>
</body>
</html>
