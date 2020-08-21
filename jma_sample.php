#!/usr/local/bin/php
<?php
	include 'jma.phps';

	$data	= JMA_get('34186');
	foreach($data['data'] as $h)
	{
		foreach($data['info'] as $key => $i){
			echo $h[ $key ].",";
		}
		echo "\n";
	}
?>
