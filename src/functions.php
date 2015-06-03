<?php
	
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