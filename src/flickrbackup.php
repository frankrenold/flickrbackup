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
  
/** Load Config and Access token from config files **/
$config = loadConfig();

var_dump($config);
