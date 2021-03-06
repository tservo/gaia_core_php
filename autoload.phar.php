<?php

// autoload all the vendor libs.
spl_autoload_register(function($classname) {
    // force classname to lowercase ... make sure we are standardizing it.
    $class = strtolower($classname);
    
    // load facebook related classes.
    if( $class == 'facebook' ) {
        return @include  'phar://'. __DIR__ . '/bin/facebook.phar/facebook.php';
    }
    if( $class == 'basefacebook' ) {
        return @include 'phar://'. __DIR__ . '/bin/facebook.phar/base_facebook.php';
    }
    
    // load yaml vendor classes
    if( $class == 'sfyaml' ) {
        return @include 'phar://'. __DIR__ . '/bin/sfyaml.phar/sfYaml.php';
    }
    
    // load pheanstalk
    if( $class == 'pheanstalk') {
        $base = 'phar://' . __DIR__ . '/bin/pheanstalk.phar';
        include $base . '/Pheanstalk/ClassLoader.php';
        Pheanstalk_ClassLoader::register($base);
        include $base . '/Pheanstalk.php';
    }
    
    // load the gaia namespaced classes.
    if( substr( $class, 0, 5) == 'gaia\\' ) 
        return include  'phar://' . __DIR__ . '/bin/gaia_core_php.phar/' .strtr($class, '\\', '/').'.php';   
        
    // load predis namespaced classes.
    if( substr($class, 0, 7) == 'predis\\' ) {
        return include 'phar://' . __DIR__.'/bin/predis.phar/'.strtr($classname, '\\', '/').'.php';
    }
    // all done.
});

