<?php
namespace Gaia\Cache;
use Gaia\Instrumentation;

class Instrumented extends Base  {

    public function get( $k, $options = NULL ){
        $multi = FALSE;
        if( is_scalar( $k ) ){
            self::stat('mc_getkey_count');
        } elseif( is_array( $k ) ){
                $multi = count( $k );
                if( $multi < 1 ) return array();
                self::stat('mc_getkey_count', $multi );
        } else {
            return FALSE;
        }
        self::stat('mc_get_count');
        $_ts = microtime(TRUE);
        $res = parent::get( $k );
        self::stat('mc_get_time', microtime(TRUE) - $_ts );

        if( $multi ){
            if( is_array( $res ) ){
                $diff = $multi - count( $res );
                if( $diff ) self::stat('mc_miss_count', $diff );
                return $res;
            } else {
                self::stat('mc_miss_count', $multi );
                return array();
            }
        } else {
            if( ! $res ) self::stat('mc_miss_count');
            return $res;
        }
    }
    
    protected static function stat( $key, $value = 1 ){
        Instrumentation::increment($key, $value);
    }
    
    public function add( $k, $v, $compress = 0, $ttl = NULL ){
        $_ts = microtime(TRUE);
        self::stat('mc_add_count');
        $res = parent::add($k, $v, $compress, $ttl );
        self::stat('mc_add_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function set( $k, $v, $compress = 0, $ttl = NULL ){
        $_ts = microtime(TRUE);
        self::stat('mc_set_count');
        $res = parent::set($k, $v, $compress, $ttl );
        self::stat('mc_set_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function replace( $k, $v, $compress = 0, $ttl = NULL ){
        $_ts = microtime(TRUE);
        self::stat('mc_replace_count');
        $res = parent::replace($k, $v, $compress, $ttl );
        self::stat('mc_replace_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function increment( $k, $v = 1 ){
        $_ts = microtime(TRUE);
        self::stat('mc_increment_count');
        $res = parent::increment($k, $v );
        self::stat('mc_increment_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function decrement( $k, $v = 1 ){
        $_ts = microtime(TRUE);
        self::stat('mc_increment_count');
        $res = parent::decrement($k, $v );
        self::stat('mc_increment_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function delete( $k ){
        $_ts = microtime(TRUE);
        self::stat('mc_delete_count');
        $res = parent::delete( $k, 0);
        self::stat('mc_delete_time', microtime(TRUE) - $_ts );
        return $res;
    }
}
