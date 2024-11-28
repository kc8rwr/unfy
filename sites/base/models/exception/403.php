<?php

class Exception_403 extends Exception {

	public function __construct($_message = 'Forbidden', $_code = '403') {
		parent::__construct($_message, $_code);
	}

}

?>