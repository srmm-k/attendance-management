coachtech勤怠管理アプリ

このアプリケーションは、ユーザーの登録、出勤、退勤、休憩時間の登録、申請、ユーザーの管理、申請の承認機能を持つ勤怠管理アプリです。

環境構築

Dockerビルド　　

【コマンドライン上】　　

・ git clone git@gtihub.com:coachtech-material/laravel-docker-template.git

・ mv laravel-docker-template attendance-management

・ cd attendance-management

・ docker-compose up -d --build (docker-compose.yml,nginx,php(dockerfile),mysqlを適宜変更)

Laravel環境構築  

・ docker-compose exec php bash  
・ composer install  
・ composer -v  
・ cp .env.example .env,環境変数を適宜変更  
・ php artisan key:generate APP_KEYにランダムなキーを作成
・ php artisan make:command MakeBladeCommand,blade.phpのテンプレート作成  
・ php artisan make:blade register ...各必要なviewの作成  
・ php artisan make:controller AttendanceController ...各必要なcontrollerを作成（アッパーキャメル）  
・ php artisan make:model Application ...各必要なmodelを作成（アッパーキャメル）  
・ php artisan make:request LoginRequest ...各必要なRequestを作成（アッパーキャメル）  
・ php artisan make:migration create_attendance_table ...各必要なtableを作成（スネークケース）  
・ マイグレーションファイル編集後、php artisan migrate　実行  
・ php artisan make:seeder AttendanceTablesSeeder ...各必要なSeederを作成（アッパーキャメル）  
・ ダミーデータの作成  
・ php artisan db:seed 実行  
・ php artisan test

URL  
・開発環境：http://localhost/  
・phpMyAdmin:http://localhost:8070/  

使用技術（実行環境）  
・php：8.1.33  
・laravel：8.83.8  
・Myspl：8.0.26  
・nginx：1.21.1  

ER図  
<img width="1489" height="1286" alt="image" src="https://github.com/user-attachments/assets/1758c81f-4a3d-42b5-b035-42f73e12d336" />
