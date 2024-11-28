<?php

class Exception_400 extends Exception {

	public function __construct($_message = 'Forbidden', $_code = '400') {
		parent::__construct($_message, $_code);
	}

}

?>