<?php
function wpcpn_array_search_for_array($array, $search_array) {
	if ( ! is_array($array) ) return false;

	$found = true;
	foreach( $array as $ar ) {
		$found = true;
		foreach( $search_array as $key => $value )  {
			if ( isset($ar[$key]) && $ar[$key] == $value )
				$found &= true;
			else
				$found &= false;
		}
		if ( $found ) return true;
	}

	return false;
}
