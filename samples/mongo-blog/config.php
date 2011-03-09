<?php

// アプリケーションディレクトリ
define('BASE_DIR', dirname(__FILE__));

// 配布物のルートディレクトリ
define('ROOT_DIR', dirname(dirname(BASE_DIR)));

// Kioskライブラリの読み込み
require_once ROOT_DIR. '/Kiosk.php';

// データソース「mongo」の定義
$source = Kiosk::source(
	'mongo', 
	array(
		'type' => 'Mongo', 
		'dbname'=> 'kiosksamples', 
	)
);

// クラスの定義
class Post extends Kiosk {
}

// ================================ CASE 0 最低限


// クラスとKioskの接続
Post::bind($source, array(
));


















//return;

// ================================ CASE 1 シンプルケース


// クラスとKioskの接続
Post::bind($source, array(
	// コレクション名を指定
	'name' => 'posts', 
	
	// カラム定義
	'columns' => array(
		'author',
		'text',
	),
));

//return;

























// ================================ CASE 2 初期値を提供


// クラスとKioskの接続
Post::bind($source, array(
	// コレクション名
	'name' => 'posts', 
	
	// カラム定義
	'columns' => array(
		'author',
		'text',
		
		'post_at' => array(
			'default' => Kiosk::func(function() {
				return new MongoDate();
			}),
		),
	),
));





















// ================================ CASE 3 ユーザードキュメントに連携

class User extends Kiosk {
}

// クラスとKioskの接続

Post::bind($source, array(
	// コレクション名
	'name' => 'posts', 
	
	// カラム定義
	'columns' => array(
		'author' => array(
			'type' => 'User', 
		),
		
		'text',
		
		'post_at' => array(
			'default' => Kiosk::func(function() {
				return new MongoDate();
			}),
		),
	),
));

User::bind($source, array(
	// コレクション名
	'name' => 'users', 
	
	// カラム定義
	'columns' => array(
		'name',
		'posts' => array(
			'type' => 'hasMany',	// Pseudo column
			'class' => 'Post', 		// 関連先のクラス名
		),
	),
));

