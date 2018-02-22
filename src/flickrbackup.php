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
 * Get configDir from commandline args
 */
if(!isset($argv) || count($argv) <= 1) {
	echo "ERROR: Argument required. Use the path to configDir as first argument (e.g. 'php flickrbackup.php ../config/')\n";
	exit;
}
$configDir = $argv[1];
if(substr($configDir, 0, 1) != '/') {
	$configDir = __DIR__.'/'.$configDir;
}
$configDir = rtrim($configDir, '/\\');
if(!file_exists($configDir.'/config.json')) {
	echo "ERROR: No valid config file found here: ".$configDir.'/config.json'."\n";
	exit;
} else {
	$config = json_decode(file_get_contents($configDir.'/config.json'),true);
	if(!isset($config['app_key'],$config['app_secret'])) {
		echo "ERROR: Required config values 'app_key' and/or 'app_secret' not found in: ".$configDir.'/config.json'."\n";
		exit;
	}
}
if(!file_exists($configDir.'/tokens.json')) {
	echo "ERROR: No valid permission file found here: ".$configDir.'/tokens.json'."\n".
		"Use getperms.php to give and save permissions to make sure FlickrBackup has access to your flickr account.\n";
	exit;
} else {
	$config = json_decode(file_get_contents($configDir.'/tokens.json'),true);
	if(!isset($config['access_token'],$config['access_secret'])) {
		echo "ERROR: Required permission values 'access_token' and/or 'access_secret' not found in: ".$configDir.'/tokens.json'."\n".
		"Use getperms.php to give and save permissions to make sure FlickrBackup has access to your flickr account.\n";
		exit;
	}
}

/**
 * Bootstrap the library
 */
require_once __DIR__ . '/../vendor/autoload.php';
$b = new Backup($configDir, __DIR__.'/../data');

/**
 * Run Backup
 */
$b->loadMedia();

/**
 * Finish with insights
 */
$b->stats();
exit;