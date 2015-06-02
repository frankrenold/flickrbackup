<?php

/**
 * Bootstrap
 * 
 * @vendor frankrenold
 * @package flickrbackup
 * @license opensource.org/licenses/MIT The MIT License (MIT)
 */

/**
 * Bootstrap the library
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Setup error reporting
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Setup the timezone
 */
ini_set('date.timezone', 'Europe/Amsterdam');

/**
 * @var $serviceFactory \OAuth\ServiceFactory An OAuth service factory.
 */
$serviceFactory = new \OAuth\ServiceFactory();
