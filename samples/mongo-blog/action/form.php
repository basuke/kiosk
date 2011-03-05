<?php

/**
 *	フォーム処理
 */

$post = Post::create();

if (!empty($_POST)) {
	$post = save($_POST);
	
	if ($post->id) {
		redirect('./');
	}
}

// function save($data) {
// 	$post = Post::create($data);
// 	$post->save();
// 	return $post;
// }
// 
function save($data) {
	$user = User::find(array('first', 'name' => $data['author']));
	if (!$user) {
		$user = User::create(array('name' => $data['author']));
		$user->save();
	}
	
	$post = Post::create(array(
		'text' => $data['text'], 
		'author' => $user, 
	));
	$post->save();
	return $post;
}

// 以下ビューコード

?>
<form action="./?action=form" method="post">
	<dl>
		<dt>author</dt>
		<dd>
			<input type="text" name="author" value="<?php 
				echo @$post->author;
			?>">
		</dd>
		<dt>text</dt>
		<dd>
			<textarea name="text" rows="5" cols="60"><?php 
				echo @$post->text;
			?></textarea>
		</dd>

	</dl>
	<div><input type="submit" value="Post"></div>
</form>

<hr>
<a href="./">back</a>


