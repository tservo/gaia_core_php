<?php
namespace Gaia\Stockpile\Storage\SQLite;
use \Gaia\DB\Driver\PDO;
use \Gaia\Stockpile\Exception;
use \Gaia\DB\Transaction;
use \Gaia\Stockpile\Storage\Iface;
use \Gaia\Store;

class Core implements Iface {
    protected $db;
    protected $app;
    protected $user_id;
    public function __construct( \Gaia\DB $db, $app, $user_id, $dsn){
        if( ! $db->isa('sqlite') ) throw new Exception('invalid driver', $db );
        $this->db = $db;
        $this->app = $app;
        $this->user_id = $user_id;
        if( ! \Gaia\Stockpile\Storage::isAutoSchemaEnabled() ) return;
        $cache = function_exists('apc_fetch') ? new Store\Gate( new Store\Apc() ) : new Store\KVP;
        $key = 'stockpile/storage/__create/' . md5( $dsn . '/' . $app . '/' . get_class( $this ) );
        if( $cache->get( $key ) ) return;
        if( ! $cache->add( $key, 1, 60 ) ) return;
        $this->create();
    }
    
    public function create(){
        $table = $this->table();
        $rs = $this->execute("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' and `name` = %s", $this->table());
        $row = $rs->fetch();
        if( $row ) return TRUE;
        $rs = $this->execute($this->sql('CREATE'));
        if( ! $rs ) return FALSE;
        if( $sql = $this->sql('INDEX') ) return $this->execute( $sql );
        return TRUE;
    }
    
    protected function table(){
        return $this->app . '_stockpile_' . constant(get_class( $this ) . '::TABLE' );
    }
    
    protected function sql( $name ){
        return $this->injectTableName( constant(get_class($this) . '::SQL_' . $name) );
    }
    
    protected function injectTableName( $query ){
        return str_replace('{TABLE}', $this->table(), $query );
    }
    
    protected function execute( $query /*, .... */ ){
        if( ! Transaction::atStart() ) Transaction::add( $this->db );
        $args = func_get_args();
        array_shift( $args );
        $rs = $this->db->execute( $qs = $this->db->prep_args( $query, $args ) );
        //print "#    " . $qs ."\n";
        if( ! $rs ){
            throw new Exception('database error', $this->dbInfo($qs) );
        }
        return $rs;
    }
    
    protected function dbinfo($qs = NULL){
        return  array('db'=> $this->db, 'query'=>$qs, 'error'=>$this->db->error());
    }
    
    protected function claimStart(){
        if( ! Transaction::claimStart() ) return FALSE;
        Transaction::add( $this->db );
        return TRUE;
    }
}