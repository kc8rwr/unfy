<?php

class Exception_500 extends Exception {

	public function __construct($_message = 'Forbidden', $_code = '500') {
		parent::__construct($_message, $_code);
	}

}

?>