<?php

class Exception_Redirect extends Exception {

	public function __construct($_address) {
					header("Location: {$_address}");
					die;
	}

}

?>