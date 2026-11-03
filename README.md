# CourseHub

オンライン学習プラットフォーム。コーチがコースを作成し、受講生が学習・進捗管理できるシステム。

## 環境構築

開発環境は [Laravel Sail](https://laravel.com/docs/10.x/sail)（Docker）で動かします。

### 前提条件

- Docker Desktop がインストールされていること
  - Windows（WSL2）の場合は、Docker Desktop の設定で WSL Integration を有効にしてください

### セットアップ手順

1. リポジトリをクローン

```bash
git clone https://github.com/coachtech-material/pro-cc-coursehub.git
cd pro-cc-coursehub
```

2. `.env` ファイルを作成

```bash
cp .env.example .env
```

作成した `.env` の次の項目を書き換えます。

```
DB_HOST=mysql
DB_USERNAME=sail
DB_PASSWORD=password
QUEUE_CONNECTION=sync
```

> `DB_HOST` は `127.0.0.1` ではなく、`compose.yaml` のサービス名 `mysql` を指定します（アプリはコンテナ内から DB に接続するため）。

3. 依存パッケージをインストール

アプリのコンテナは `vendor/laravel/sail/runtimes/8.2/Dockerfile` からビルドされます。このファイルは `composer install` で作られるので、**Docker を起動する前に**実行してください。

ホストに PHP 8.2 と Composer がある場合:

```bash
composer install
```

ホストに PHP がない場合（Docker で実行）:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

4. Docker を起動

```bash
./vendor/bin/sail up -d
```

初回はイメージのビルドがあるので数分かかります。`docker compose up -d` でも起動できます。

5. アプリケーションキーの生成

```bash
./vendor/bin/sail artisan key:generate
```

6. マイグレーション・シーディング

```bash
./vendor/bin/sail artisan migrate --seed
```

7. フロントエンドのビルド

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

開発中は `npm run dev` を起動したままにします。ビルドだけ行う場合は `./vendor/bin/sail npm run build` を実行します。

### sail エイリアス（任意）

毎回 `./vendor/bin/sail` と打つ手間を省けます。

```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

以降は `sail artisan migrate` のように実行できます。

### アクセス

- アプリケーション: http://localhost
- phpMyAdmin: http://localhost:8080

### ログイン用アカウント

シーディングで次のアカウントが作られます。パスワードはすべて `password` です。

| ロール | メールアドレス |
|---|---|
| admin | `admin@coursehub.com` |
| coach | `coach1@coursehub.com` 〜 `coach3@coursehub.com` |
| student | `student@coursehub.com` |

### キュー設定

ローカル開発では `QUEUE_CONNECTION=sync` を使います。`compose.yaml` には Redis が含まれていないので、ローカルで `redis` を設定するとジョブを入れたときにエラーになります。

本番環境では Redis を使います。

```
QUEUE_CONNECTION=redis
```

### トラブルシューティング

- **`docker compose up` で Dockerfile が見つからない**: `composer install` を実行していません。手順 3 を先に行ってください。
- **DB に接続できない**: `.env` が `DB_HOST=mysql` になっているか確認してください。
- **ポート 80 または 3306 が使用中**: `.env` に `APP_PORT=8000` や `FORWARD_DB_PORT=3307` を追加して、ポートを変えてください。

## テスト

```bash
./vendor/bin/sail artisan test
```

## コーディング規約

### バリデーション
- Controller ではバリデーションに Form Request を使用する
- Form Request は `app/Http/Requests/` に配置する

### 命名規則
- 変数名・メソッド名は camelCase
- テーブル名・カラム名は snake_case
- Controller 名は単数形 + Controller（例: CourseController）

### エラーハンドリング
- Blade 画面はリダイレクトでエラーを返す
- フラッシュメッセージで結果を通知する

### テスト
- 新機能には Feature テストを書く
- テストメソッド名は `test_` プレフィックス

## 設計方針

### Controller
- Controller は薄く保つ。ビジネスロジックが複雑な場合は Service クラスに切り出す

### Service クラス
- 複数モデルにまたがる処理は Service クラスに集約する（例: EnrollmentService）
- `app/Services/` に配置する

### Policy
- リソースのアクセス制御は Policy で実装する
- Controller で `$this->authorize()` を使用する

### イベント
- 重要なドメインイベントは Event/Listener パターンで実装する

## 技術スタック

- PHP 8.2
- Laravel 10
- Laravel Sail（Docker 開発環境）
- MySQL 8.0
- Tailwind CSS
