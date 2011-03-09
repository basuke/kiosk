<style>

*, p, a, h1, h2, h3, h4, h5, h6 {
	color: white;
}

body {
	background: black;
}

</style>

<h1>Dump All Collections</h1>
<?php

if (!empty($_GET['init'])) {
	foreach ($source->db->listCollections() as $collection) {
		$collection->drop();
	}
	
	redirect('./?action=dump');
}

foreach ($source->db->listCollections() as $collection) {
	echo "<hr>";
	echo "<h2>", $collection->getName(), "</h2>";
	
	echo "<pre>";
	var_export(iterator_to_array($collection->find(), false));
	echo "</pre>";
}

?>
<hr>
<a href="./?action=dump&init=1">Drop all</a>
