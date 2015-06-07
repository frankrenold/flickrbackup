<?php
	




$setname = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(array(' ','/'), '-', $set['title']['_content']));
			$setsInfo[$set['id']] = array('name' => $setname);

function getSetInfo($f, $setid) {
	global $flickrCalls;
	$perPage = 500;
	$ps = $f->photosets_getPhotos($setid,'date_taken',5,$perPage,1);
	$flickrCalls++;
	$photos = $ps['photoset']['photo'];
	for($p=2; $p<=$ps['photoset']['pages']; $p++) {
		$pst = $f->photosets_getPhotos($setid,'date_taken',5,$perPage,$p);
		$flickrCalls++;
		$photos = array_merge($photos, $pst['photoset']['photo']);
	}
	$ids = array();
	foreach($photos as $photo) {
		$ids[] = $photo['id'];
	}
	usort($photos, 'cmpSetByDateTaken');
	return array('startDate' => strtotime($photos[0]['datetaken']), 'photoIds' => $ids);
}

function cmpSetByDateTaken($a, $b) {
	return (strtotime($a['datetaken']) < strtotime($b['datetaken'])) ? -1 : 1;
}