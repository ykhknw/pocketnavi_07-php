# HETEML移設手順書

## 概要
PocketNaviアプリケーションをHETEMLレンタルサーバーに移設する手順です。

## 前提条件
- HETEMLアカウント
- データベース名: `_shinkenchiku_02`
- テーブル構造は既に作成済み

## 移設手順

### 1. ファイルのアップロード
1. プロジェクト全体をHETEMLのドキュメントルート（通常は`public_html`）にアップロード
2. 以下のファイルを除外：
   - `logs/` ディレクトリ（存在する場合）
   - `.git/` ディレクトリ
   - `node_modules/` ディレクトリ（存在する場合）

### 2. データベース設定の確認
1. `config/database.php` の設定を確認
2. HETEMLのデータベース情報に合わせて調整：
   ```php
   private $host = 'localhost';
   private $db_name = '_shinkenchiku_02';
   private $username = 'HETEMLのDBユーザー名';
   private $password = 'HETEMLのDBパスワード';
   ```

### 3. 必要なテーブルの確認
以下のテーブルが `_shinkenchiku_02` データベースに存在することを確認：
- `architect_compositions_2`
- `buildings_table_3`
- `building_architects`
- `individual_architects_3`

### 4. ファイル権限の設定
```bash
chmod 755 logs/
chmod 644 *.php
chmod 644 includes/*.php
chmod 644 config/*.php
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js
```

### 5. .htaccessの確認
`.htaccess` ファイルが正しくアップロードされていることを確認

### 6. 動作確認
1. メインページにアクセス: `https://yourdomain.com/`
2. 検索機能のテスト
3. 建築家ページのテスト: `https://yourdomain.com/architects/sample-slug/`
4. 建築物ページのテスト: `https://yourdomain.com/buildings/sample-slug/`

## トラブルシューティング

### データベース接続エラー
- データベース名、ユーザー名、パスワードを確認
- HETEMLのコントロールパネルでデータベース設定を確認

### 404エラー
- `.htaccess` ファイルが正しくアップロードされているか確認
- HETEMLでmod_rewriteが有効になっているか確認

### 権限エラー
- `logs/` ディレクトリの書き込み権限を確認
- ファイルの所有者と権限を確認

## 注意事項
- HETEMLでは一部のPHP関数が制限されている場合があります
- メール送信機能はHETEMLの制限に注意してください
- ログファイルのサイズ管理に注意してください

## バックアップ
移設前に必ず以下をバックアップしてください：
- データベース全体
- アップロードしたファイル
- 設定ファイル
