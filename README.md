# Atte
### 勤怠管理システム  
ユーザー登録しログインすることで勤務開始、休憩開始、休憩終了、勤務終了の勤怠を可能とし  
勤怠は日付一覧で確認することができます
![スクリーンショット 2024-09-29 021853](https://github.com/user-attachments/assets/9e04435e-d033-4af6-8136-65ff1d443e0f)

## 作成した目的
人事評価のため

## アプリケーションURL
- 開発環境:http://localhost  
(勤怠ページに遷移するにはユーザー登録及びログインが必要です)
- phpMyAdmin:http://localhost:8080


## 機能一覧
- ログイン、ログアウト機能
- 勤怠
- 日付別勤怠一覧  
- 個別勤怠一覧

## 使用技術
- PHP:7.4.9
- Laravel:8.83.8
- MySQL:8.0.26

## テーブル設計  
![テーブル設計](https://github.com/user-attachments/assets/27c8be27-646f-4b4c-bf37-be3d3d27ed54)

## ER図
![attendace drawio (2)](https://github.com/user-attachments/assets/a3e96021-43fb-43e0-b98e-7484a3de7ad7)

## 環境構築  
### Dockerビルド
1. `git clone git@github.com:yuji-oonaka/atte-system.git`
2. DockerDesktopアプリを立ち上げる
3. `docker-compose up -d --build`
>*MacのM1・M2チップのPCの場合、`no matching manifest for linux/arm64/v8 in the manifest list entries`のメッセージが表示されビルドができないことがあります。 エラーが発生する場合は、docker-compose.ymlファイルの「mysql」内に「platform」の項目を追加で記載してください*
```
mysql:
    platform: linux/x86_64(この文追加)
    image: mysql:8.0.26
    environment:
```

### Laravel環境構築
1. `docker-compose exec php bash`
2. `composer install`
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
4. .envに以下の環境変数を追加
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
5. アプリケーションキーの作成
```
php artisan key:generate
```
6. マイグレーションの実行
```
php artisan migrate
```
7.シーディングの実行
```
php artisan db:seed
```
### シーディングについて
10人のユーザーが作成され、各ユーザーに対して30日分の勤怠記録と休憩時間がランダムに生成されます
