<?php
	
/**
 * Load config and users access credentials from file
 */
function loadConfig() {
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
	
	$configFile = __DIR__.'/../config/config.json';
	if(file_exists($configFile)) {
		$config = json_decode(file_get_contents($configFile),true);
		if(!is_array($config) || !array_key_exists('app_key', $config) || !array_key_exists('app_secret', $config)) {
			echo "No valid Flickr app keys found in '".$config."'. Update this file with your flickr api key.\n";
			exit;
		}
	} else {
		echo "Config file '".$configFile."' does not exist. Create it from example.\n";
		exit;
	}
	return array_merge($config, $token);
}
	
/**
 * Takes an __PHP_Incomplete_Class and casts it to a stdClass object.
 * All properties will be made public in this step.
 *
 * @since  1.1.0
 * @param  object $object __PHP_Incomplete_Class
 * @return object
 */
function fix_object( $object ) {
    // preg_replace_callback handler. Needed to calculate new key-length.
    $fix_key = create_function(
        '$matches',
        'return ":" . strlen( $matches[1] ) . ":\"" . $matches[1] . "\"";'
    );

    // 1. Serialize the object to a string.
    $dump = serialize( $object );

    // 2. Change class-type to 'stdClass'.
    $dump = preg_replace( '/^O:\d+:"[^"]++"/', 'O:8:"stdClass"', $dump );

    // 3. Make private and protected properties public.
    $dump = preg_replace_callback( '/:\d+:"\0.*?\0([^"]+)"/', $fix_key, $dump );

    // 4. Unserialize the modified object again.
    return unserialize( $dump );
}