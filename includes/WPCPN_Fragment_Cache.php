<?php
/*
Usage:
	$frag = new CWS_Fragment_Cache( 'unique-key', 3600 ); // Second param is TTL
	if ( !$frag->output() ) { // NOTE, testing for a return of false
		functions_that_do_stuff_live();
		these_should_echo();
		// IMPORTANT
		$frag->store();
		// YOU CANNOT FORGET THIS. If you do, the site will break.
	}
*/

class WPCPN_Fragment_Cache {
	const GROUP = 'wpcpn-c_';
	public $key;
	public $ttl;
	public $network_wide;

	public function __construct( $key, $ttl, $network_wide = false ) {
		$this->key          = $key;
		$this->ttl          = $ttl;
		$this->network_wide = $network_wide;
	}

	public function output() {
		$output = get_transient( self::GROUP . $this->key );
		if ( $output  !== false ) {
			echo $output;
			return true;
		} else {
			ob_start();
			return false;
		}
	}

	public function store() {
		$output = ob_get_flush();

		set_transient( self::GROUP . $this->key, $output, $this->ttl );
	}

	public function delete() {
		delete_transient( self::GROUP . $this->key );
	}
}
