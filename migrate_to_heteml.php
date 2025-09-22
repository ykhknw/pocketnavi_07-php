<?php
/**
 * heteml移設用スクリプト
 * 現在の構成をレンタルサーバー用に再構成します
 */

echo "=== heteml移設用ファイル再構成スクリプト ===\n\n";

// 移設先ディレクトリ
$targetDir = 'heteml_deploy';

// 移設先ディレクトリを作成
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
    echo "✓ 移設先ディレクトリを作成: {$targetDir}\n";
}

// 移設するファイル・フォルダのリスト
$itemsToMove = [
    // publicフォルダの内容をルートに移動
    'public/index.php' => 'index.php',
    'public/about.php' => 'about.php',
    'public/contact.php' => 'contact.php',
    'public/api' => 'api',
    'public/assets' => 'assets',
    'public/apply_indexes.php' => 'apply_indexes.php',
    'public/create_indexes_manual.php' => 'create_indexes_manual.php',
    
    // アプリケーションコード
    'src' => 'src',
    'config' => 'config',
    'database' => 'database',
    
    // その他の必要なファイル
    'favicon.ico' => 'favicon.ico',
    'landmark.ico' => 'landmark.ico',
];

// ファイル・フォルダをコピー
foreach ($itemsToMove as $source => $destination) {
    $sourcePath = $source;
    $targetPath = $targetDir . '/' . $destination;
    
    if (file_exists($sourcePath)) {
        if (is_dir($sourcePath)) {
            // ディレクトリの場合
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            copyDirectory($sourcePath, $targetPath);
            echo "✓ ディレクトリをコピー: {$source} → {$destination}\n";
        } else {
            // ファイルの場合
            $targetDirPath = dirname($targetPath);
            if (!is_dir($targetDirPath)) {
                mkdir($targetDirPath, 0755, true);
            }
            copy($sourcePath, $targetPath);
            echo "✓ ファイルをコピー: {$source} → {$destination}\n";
        }
    } else {
        echo "⚠ ファイルが見つかりません: {$source}\n";
    }
}

// 設定ファイルをheteml用に調整
adjustConfigForHeteml($targetDir);

// .htaccessファイルを作成
createHtaccessFile($targetDir);

echo "\n=== 移設完了 ===\n";
echo "移設先ディレクトリ: {$targetDir}\n";
echo "このディレクトリの内容をhetemlのpublic_htmlにアップロードしてください。\n";

/**
 * ディレクトリを再帰的にコピー
 */
function copyDirectory($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            
            if (is_dir($srcFile)) {
                copyDirectory($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
    }
    closedir($dir);
}

/**
 * heteml用に設定ファイルを調整
 */
function adjustConfigForHeteml($targetDir) {
    // database_heteml.phpをdatabase.phpにコピー
    $hetemlConfig = $targetDir . '/config/database_heteml.php';
    $mainConfig = $targetDir . '/config/database.php';
    
    if (file_exists($hetemlConfig)) {
        copy($hetemlConfig, $mainConfig);
        echo "✓ heteml用データベース設定を適用\n";
    }
    
    // 環境設定ファイルを作成
    $envContent = "<?php\n";
    $envContent .= "// heteml環境設定\n";
    $envContent .= "define('ENVIRONMENT', 'production');\n";
    $envContent .= "define('DEBUG_MODE', false);\n";
    $envContent .= "define('LOG_LEVEL', 'error');\n";
    
    file_put_contents($targetDir . '/config/env.php', $envContent);
    echo "✓ 環境設定ファイルを作成\n";
}

/**
 * .htaccessファイルを作成
 */
function createHtaccessFile($targetDir) {
    $htaccessContent = "# heteml用 .htaccess\n";
    $htaccessContent .= "RewriteEngine On\n";
    $htaccessContent .= "\n";
    $htaccessContent .= "# セキュリティヘッダー\n";
    $htaccessContent .= "Header always set X-Content-Type-Options nosniff\n";
    $htaccessContent .= "Header always set X-Frame-Options DENY\n";
    $htaccessContent .= "Header always set X-XSS-Protection \"1; mode=block\"\n";
    $htaccessContent .= "\n";
    $htaccessContent .= "# キャッシュ設定\n";
    $htaccessContent .= "<IfModule mod_expires.c>\n";
    $htaccessContent .= "    ExpiresActive On\n";
    $htaccessContent .= "    ExpiresByType text/css \"access plus 1 month\"\n";
    $htaccessContent .= "    ExpiresByType application/javascript \"access plus 1 month\"\n";
    $htaccessContent .= "    ExpiresByType image/png \"access plus 1 month\"\n";
    $htaccessContent .= "    ExpiresByType image/jpg \"access plus 1 month\"\n";
    $htaccessContent .= "    ExpiresByType image/jpeg \"access plus 1 month\"\n";
    $htaccessContent .= "    ExpiresByType image/gif \"access plus 1 month\"\n";
    $htaccessContent .= "    ExpiresByType image/svg+xml \"access plus 1 month\"\n";
    $htaccessContent .= "</IfModule>\n";
    $htaccessContent .= "\n";
    $htaccessContent .= "# ファイルアクセス制限\n";
    $htaccessContent .= "<Files \"config/*\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    $htaccessContent .= "\n";
    $htaccessContent .= "<Files \"src/*\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    $htaccessContent .= "\n";
    $htaccessContent .= "<Files \"database/*\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    
    file_put_contents($targetDir . '/.htaccess', $htaccessContent);
    echo "✓ .htaccessファイルを作成\n";
}
?>
