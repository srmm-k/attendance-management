coachtech勤怠管理アプリ

環境構築

Dockerビルド  
１：git clone リンク  
２：docker-compose up -d -build

※docker-compose.ymlを必要があれば適宜編集

Laravel環境構築

１：docker-compose exec php bash  
２：composer install  
３：cp .env.example .envコマンドで.envを作成し、環境変数を変更   
４：
