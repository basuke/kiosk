.. Kiosk documentation master file, created by
   sphinx-quickstart on Fri Jan 21 11:46:34 2011.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

=================
Kioskドキュメント
=================

目次:

.. toctree::
   :maxdepth: 2

-----------------
Kiosk Data とは？
-----------------

Kiosk dataはPHPのためのデータ層へのアクセスライブラリです。よく使われる言葉で
言えば **O/Rマッパー** とか **パーシスタントレイヤー（永続化層）** とか呼ばれる
たぐいのものです。もしあなたが **CakePHP** を使っているなら、 **モデル** と
呼んでいる部分がそれに当たるかもしれません。ともかく、データベースから
データを呼び出したり、検索、保存することに使えます。

Kioskの特徴は、取得した値を使いやすくすること、コードの記述量を減らすことを
最大の目的にしています。

簡単な使い方を見てみましょう。設定が終わっているとして::
	
	<?php
	
	// 表示可能なUserオブジェクトを年齢降順で表示する
	
	$users = User::find(array(
		'conditions' => array('hidden'=>false), 
		'order' => '-age'
	));
	
	foreach ($users as $user) {
		// $user 配列ではなくはUserクラスのオブジェクト
		
		echo $user->name, ': ', $user->age, "\n";
		
		// オブジェクトなので当然メソッドも呼び出せる
		
		if ($user->isActive()) {
			...
		}
	}

--------------------------------
Kioskがサポートするデータソース
--------------------------------

Kioskは、一般的なデータベースからデータを読み込むのが一般的ですが、それ以外に
以下のデータソースをサポートしています。

データベース
+++++++++++++

PostgreSQL, MySQL, Sqliteをサポートします。また、PDOをサポートしているので、
PDOに対応するその他のデータベースも使うことが出来ます。

データベースでは、参照を使うことが出来ます。参照を使うと以下のような記述が
使用可能になります。::

	<?php
	
	// Userテーブルと1:1の関連を持つResultテーブルのscore値でソートする
	
	$users = User::find(array(
		'order' => '-result.score'
	));
	
	// UserがrefersToの関連を持つGroupテーブルのscore値を条件に検索する
	
	$users = User::find(array(
		'conditions' => array('group.name' => 'Techno')
	));
	

MongoDB
++++++++

KioskはMongoDBをネイティブでサポートします。

ファイル
++++++++

CSVなどの構造化されたファイルであれば、データソースとして使用することが出来ます。

-------
設定
-------

Kiosk dataを使うためには、データソースとオブジェクトを結びつける必要が
あります。



-----------------
PHP 5.3 以前では
-----------------

残念ながら、PHP 5.3以前のクラスには大きな制約があり、前のサンプルのようには
動作させられません。そこでKioskは、自動的にグローバルな関数を定義することで
この問題に対処しています。 *クラス名*\ ::\ *メソッド* に相当する 
*クラス名*\ _\ *メソッド* を定義します。先の例は、以下のようにも書けます::
	
	<?php
	
	// 関数を使ってUserクラスにアクセス
	
	$users = User_find(array(
		'conditions' => array('hidden'=>false), 
		'order' => '-age'
	));

グローバル関数を定義することについて、非難の声が避けられないのは覚悟しています。
Kioskは利便性を最大の目標に掲げています。言ってみれば、クラスを定義することも
グローバルな名前を一つ確保することと変わりません。その名前をプレフィックスとして
持つ関数を定義することには、それほど名前空間を汚すことには当たらないと考えます。

.. note:: なお、この関数の自動登録機能は、設定で無効にすることも出来ます。

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`

