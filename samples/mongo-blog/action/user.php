<?php

/**
 *	ユーザーのポスト一覧
 */

$author = Post::load($_GET['author']);

$posts = $author->fetch('posts', array(
	'order' => '-post_at', 
));

// 以下ビューコード

?>

<h2><?php echo $author->name ?></h2>

<div><?php echo count($posts) ?> posts</div>

<?php foreach ($posts as $post): $post->fetch('author') ?>
	<hr>
	<div>
		<p><?php echo $post->text ?></p>
	</div>
<?php endforeach ?>

<hr>

<a href="./">back</a> | 
<a href="./?action=form">new post</a>
