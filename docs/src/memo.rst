メモ書き
=================================

Kiosk DataとCakePHP
-----------------------

Kioskデータレイヤーは、CakePHPのモデルを使った不満から生まれたものです。

不満

- コントローラとモデルが密接すぎる
- モデル＝データベースレイヤととらわれすぎている
- ビジネスロジックの行き場所がない
- 複数の「モデル」にまたがるロジックをどこに書けばいいのか
- オブジェクトではなく配列にしてしまうのがもったいない
- 1クラス＝1オブジェクトというのは、制約が多すぎる
- ストレージ層のデータは一元的でも、アプリケーション上のデータは多面性があるはず

モデルは、アプリケーションのほとんどすべて。二度は書きたくない処理はすべてモデル。
コントローラは、極力薄いレイヤーであるべき。

俺的MVC理解。

- コントローラは、リクエストに対してモデルを駆使して結果に必要な情報を集める
- ビューはコントローラが集めた情報をもとに画面を作り、次のリクエストへ誘導する
- モデルは残りの全て。ロジックでありデータであり、
  一連の処理である。モデルは参照透明性(referencial transparent）で
  あるべき。つまりINがきまればOUTが決まる存在であるべき。
  外部の環境には影響されない。影響されるならば、オブジェクトの
  生成時にその環境が取り込まれているべき。

グローバル関数が悪い訳でない。テストが大変になりがち、というだけ。
同じ理由でstaticも悪い訳ではない。
クラス宣言はグローバル関数相当である

Kioskが目指したのは、こんな使い方です。

$user = User::create();
$user->name = 'Jhon Doh';
$user->save();


$users = User::find(array(
	'conditions'=>array(
		'score >' = 80, 
	), 
	'order' => '-score', 
);

$user->score = 80;
$user->save();



