<?php
namespace FrankRenold\FlickrBackup;

/**
 * Commandline tool to backup your photos from flickr
 * 
 * Easily backup your flickr photos and movies to your filesystem
 * 
 * @vendor FrankRenold
 * @package FlickrBackup
 * @author Frank Renold <frank.renold@gmail.com>
 * @license opensource.org/licenses/MIT The MIT License (MIT)
 */
 
/**
 * Setup error reporting
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/**
 * Bootstrap the library
 */
require_once __DIR__ . '/../vendor/autoload.php';
$b = new Backup(__DIR__.'/../config',__DIR__.'/../data');


$b->loadMedia(20);

$b->stats();

exit;

// cache
$setCache = false;
$setsInfo = getAllSets($f, $config['userid']);
if(count($found['photo']) > count($setsInfo)/2) {
	// cache all photosetsinfo
	foreach($setsInfo as $setId => $setInfo) {
		$setsInfo[$setId] = array_merge($setInfo, getSetInfo($f, $setId));
	}
	$setCache = true;
} else {
	$setsInfo = array();
}

var_dump($found);
die();