# PocketNavi PHP版

日本の建築物を検索・閲覧できるWebアプリケーションのPHP版です。

## 機能

- **建築物検索**: 建築物名、建築家、場所で検索
- **建築物詳細表示**: 建築家、所在地、都道府県、建物用途、完成年、写真/動画の表示
- **地図表示**: Leafletを使用したインタラクティブマップ
- **多言語対応**: 日本語・英語の切り替え（URLクエリパラメータで制御）
- **レスポンシブデザイン**: Bootstrap 5を使用

## 技術スタック

- **バックエンド**: PHP 7.4+
- **データベース**: MySQL 5.7+
- **フロントエンド**: Bootstrap 5, Leaflet
- **アイコン**: Font Awesome 6

## セットアップ

### 1. データベースの準備

```sql
-- データベースの作成
CREATE DATABASE pocketnavi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- スキーマの実行
mysql -u root -p pocketnavi < database/schema.sql
```

### 2. 設定ファイルの編集

`config/database.php` を編集してデータベース接続情報を設定してください。

```php
private $host = 'localhost';
private $db_name = 'pocketnavi';
private $username = 'your_username';
private $password = 'your_password';
```

### 3. Webサーバーの設定

ApacheまたはNginxでWebサーバーを設定し、ドキュメントルートをプロジェクトディレクトリに設定してください。

### 4. サンプルデータの投入

実際の建築物データをデータベースに投入してください。以下のテーブルにデータを挿入する必要があります：

- `buildings_table_2`: 建築物の基本情報
- `individual_architects`: 個別建築家情報
- `architects_table`: 建築家グループ
- `architect_compositions`: 建築家の構成
- `building_architects`: 建築物と建築家の関連

## 使用方法

### 検索ページ

- URL: `index.php`
- 言語切り替え: `?lang=ja` または `?lang=en`
- 検索: 検索フォームでキーワードを入力
- 詳細検索: 写真・動画の有無でフィルタリング

### 建築物詳細ページ

- URL: `building.php?slug=建築物のスラッグ&lang=言語`
- 表示順: 建築家、所在地、都道府県、建物用途、完成年、写真/動画

### 地図機能

- 検索結果の建築物を地図上にマーカー表示
- 現在地の取得と表示
- 建築物詳細ページでは選択された建築物のみを表示

## ディレクトリ構造

```
pocketnavi-php/
├── index.php                 # メインページ（検索）
├── building.php             # 建築物詳細ページ
├── config/
│   └── database.php         # データベース設定
├── includes/
│   ├── functions.php        # 共通関数
│   ├── header.php          # ヘッダー
│   ├── footer.php          # フッター
│   ├── search_form.php     # 検索フォーム
│   ├── building_card.php   # 建築物カード
│   ├── sidebar.php         # サイドバー
│   └── pagination.php      # ページネーション
├── assets/
│   ├── css/
│   │   └── style.css       # カスタムCSS
│   ├── js/
│   │   └── main.js         # メインJavaScript
│   └── images/
│       └── favicon.ico     # ファビコン
├── database/
│   └── schema.sql          # データベーススキーマ
└── README.md               # このファイル
```

## 検索機能の詳細

### 横断検索

8つのフィールドを横断して検索します：
- 建築物名（日本語・英語）
- 建物用途（日本語・英語）
- 所在地（日本語・英語）
- 建築家名（日本語・英語）

### AND検索

全角・半角スペースで区切られたキーワードはAND条件で結合されます。

例: `安藤忠雄 住宅` → 安藤忠雄と住宅の両方が含まれる建物

## ライセンス

このプロジェクトはMITライセンスの下で公開されています。

## 貢献

プルリクエストやイシューの報告を歓迎します。

## 更新履歴

### v1.0.0 (2024年12月19日)
- 初回リリース
- 基本的な検索・詳細表示機能
- 地図表示機能
- 多言語対応