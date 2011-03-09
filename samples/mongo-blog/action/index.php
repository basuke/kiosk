<?php

/**
 *	フォーム処理
 */

$posts = Post::find(array(
	'order' => '-post_at', 
));

// 以下ビューコード

?>

<div><?php echo count($posts) ?> posts</div>

<?php foreach ($posts as $post): $post->fetch('author') ?>
	<hr>
	<div>
		<h2><?php echo $post->author->name ?></h2>
		<p><?php echo $post->text ?></p>
	</div>
<?php endforeach ?>

<hr>

<a href="./?action=form">new post</a>
