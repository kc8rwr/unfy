<?php

class Exception_404 extends Exception {

	public function __construct($_message = 'File Not Found', $_code = '404') {
		parent::__construct($_message, $_code);
	}

}

?>