<?php
namespace FrankRenold\FlickrBackup;

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

/**
 * Setup the timezone
 */
ini_set('date.timezone', 'Europe/Amsterdam');

/**
 * @var $serviceFactory \OAuth\ServiceFactory An OAuth service factory.
 */
$serviceFactory = new \OAuth\ServiceFactory();

/**
 * Create a new instance of the URI class with the current URI, stripping the query string
 */
$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
$currentUri = $uriFactory->createFromAbsolute("http://localhost/");
$currentUri->setQuery('');

// Session storage
$storage = new \OAuth\Common\Storage\Memory();

// Setup the credentials for the requests
$config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'),true);
$credentials = new \OAuth\Common\Consumer\Credentials(
	$config['app_key'],
	$config['app_secret'],
	$currentUri->getAbsoluteUri()
);

// Instantiate the Flickr service using the credentials, http client and storage mechanism for the token
$flickrService = $serviceFactory->createService('Flickr', $credentials, $storage);

// Get request token
if($token = $flickrService->requestRequestToken()){
	$oauth_token = $token->getAccessToken();
	$secret = $token->getAccessTokenSecret();
	
	if($oauth_token && $secret){
		$url = $flickrService->getAuthorizationUri(array('oauth_token' => $oauth_token, 'perms' => 'write'));
		echo "\nCopy this URL to your browser:\n".(string)$url."\n\n";
		echo "After giving permissions, flickr redirects you to a URL on localhost (intentional browser error).\nPlease copy the whole URL from your browser and enter it here: ";
		$handle = fopen ("php://stdin","r");
		$line = trim(fgets($handle));
		
		if(preg_match("/http:\/\/localhost\/\?oauth_token=(.+)&oauth_verifier=(.+)/", $line, $found)) {
			$oauth_token = $found[1];
			$oauth_verifier = $found[2];
		}
		
		if($token = $flickrService->requestAccessToken($oauth_token, $oauth_verifier, $secret)){
			$oauth_token = $token->getAccessToken();
			$secret = $token->getAccessTokenSecret();
			
			$xml = simplexml_load_string($flickrService->request('flickr.test.login'));
			print "\nLogin Status: ".(string)$xml->attributes()->stat."\n";
			print "User ID: ".(string)$xml->user->attributes()->id."\n";
			print "Username: ".(string)$xml->user->username."\n";
			print "Access Token: ".$oauth_token."\n";
			print "Access Secret: ".$secret."\n";
						
			$tokens = array(
				'user_id' => (string)$xml->user->attributes()->id,
				'user_name' => (string)$xml->user->username,
				'access_token' => $oauth_token,
				'access_secret' => $secret,
			);
			
			// save to file
			file_put_contents(__DIR__.'/../config/tokens.json', json_encode($tokens));
		}		
	}
}