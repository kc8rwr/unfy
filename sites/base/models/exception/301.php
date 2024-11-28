<?php

class Exception_301 extends Exception {

	public function __construct($_address, $_code = '301') {
					header('HTTP 1.1/ 301 Moved Permanently');
					header("Location: {$_address}");
					die;
	}

}

?>