<?php
/*
* Class that implements a list of memcache objects
*/
namespace Gaia\Cache;

class Stack {

	private $_cacher = NULL;
    protected $namespace;
    const MAX_RANGE = 5000;

    public function __construct( $namespace ){
    	$this->setNamespace($namespace);
    }
    
    public function setNamespace ( $namespace ) {
        $this->namespace = $namespace;
        $this->_cacher = $this->cacher();
	}
	
    public function add( $value, $expires = NULL ){
        if( ! ( $pos = $this->_cacher->increment('i') ) ){
            if(! $this->_cacher->add('i', 1) ) return FALSE;
            $pos = 1;
        }
        if( ! is_numeric( $expires ) || $expires < 1 ) $expires = NULL;
        $this->_cacher->set($pos, $value, 0, $expires);
        return $pos;
    }
    
    public function count(){
        $m = $this->cacher();
        $data = $m->get( array('i', 'a') );
        if( ! is_array( $data ) ) return 0;
        if( ! isset( $data['i'] ) || $data['i'] < 1) return 0;
        if( ! isset( $data['a'] ) || $data['a'] < 0 ) $data['a'] = 0;
        $ct = $data['i'] - $data['a'];
        if( $ct < 1 ) return 0;
        return $ct;
    }

	public function shift( $depth = NULL ){
	        $data = $this->_cacher->get( array('i', 'a') );
	        if( ! is_array( $data ) ) return FALSE;
	        if( ! isset( $data['i'] ) ) return FALSE;
	        if( ! isset( $data['a'] ) ) {
	            $data['a'] = 0;
	            $a_unset = TRUE;
	        }
	        if( $data['a'] < 0 ) $data['a'] = 0;
	        if( $depth !== NULL &&  $data['i'] - $data['a'] > $depth  ) $data['a'] = $depth;
	        if( $a_unset ) $this->_cacher->add('a', $data['a']);
	        while( ( $data['a'] = $this->_cacher->increment('a') ) ){
	            $res = $this->_cacher->get($data['a']);
	            $this->_cacher->delete( $data['a'] );
	            if( $res !== FALSE ) return $res;
	            if( $data['a'] >= $data['i'] ) {
	                $this->_cacher->decrement('a');
	                return FALSE;
	            }
	        }
	        return FALSE;
	}

    public function get( $k ){
        return $this->_cacher->get($k);
    }
    
    public function delete( $k ) {
    	return $this->_cacher->delete($k);
    }
    
    public function end(){
        return $this->_cacher->get('i');
    }
    
    public function start(){
        return $this->_cacher->get('a');
    }
    
    public function getRecent( $limit = 10, $reverse = false ){
        if( ! is_numeric( $limit ) || $limit > self::MAX_RANGE || $limit < 1 ) $limit = self::MAX_RANGE;
        $high = $this->_cacher->get('i');
        if( $high < 1 ) return array();
        $low = $high - $limit;
        if( $low < 1 ) $low = 1;
        if (!$reverse) {
	        return $this->get( range(  $high - ( $limit - 1), $high) );
	    } else {
	    	return $this->get( range(  $high, $high - ( $limit - 1 ) ) );
	    }
    }
    
    public function reset() {
    	$this->_cacher->delete('i');
    }
    
    protected function cacher(){
        return new Namespaced(memcache(), __CLASS__ . $this->namespace );
    }

}

