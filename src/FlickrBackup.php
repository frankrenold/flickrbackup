<?php
namespace frankrenold\flickrbackup;

/**
 * Commandline tool to backup your photos from flickr
 * 
 * Easily backup your flickr photos and movies to your filesystem
 * 
 * @vendor frankrenold
 * @package flickrbackup
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
require_once __DIR__ . '/functions.php';
  
/** Load Token from file **/
$tokensFile = __DIR__.'/../config/tokens.json';
if(file_exists($tokensFile)) {
	$tokens = json_decode(file_get_contents(__DIR__.'/../config/tokens.json'), true);
	if(is_array($tokens) && array_key_exists('Flickr', $tokens) && !empty($tokens['Flickr'])) {
		$token = unserialize($tokens['Flickr']);
	} else {
		echo "No valid Flickr token found in '".$tokensFile."'. Use getperms.php to update the tokens file.\n";
		exit;
	}
} else {
	echo "Tokensfile '".$tokensFile."' does not exist. Use getperms.php to update the tokens file.\n";
	exit;
}
$token = fix_object($token);


$token = array(
	'userid' => (string)$token->extraParams['user_nsid'],
	'username' => (string)$token->extraParams['username'],
	'fullname' => (string)$token->extraParams['fullname'],
	'accessToken' => (string)$token->accessToken,
	'accessTokenSecret' => (string)$token->accessTokenSecret,
);

$config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'),true);

$config = array_merge($config, $token);


var_dump($config);



