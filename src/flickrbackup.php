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

/**
 * Run Backup
 */
$b->loadMedia(20);

/**
 * Finish with insights
 */
$b->stats();
exit;