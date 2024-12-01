<?php

	class Controler {
	
		public $active = false;
		public $blocked_methods = Array('formerror');
		public $view = 'index';
		public $template = 'layout.php';		
		public $javascript_includes = Array('common.js');
		public $onLoad = '';
		public $title = 'Unfy';
		public $meta_keywords = '';
		public $meta_description = '';
		public $robots_index = true;
		public $robots_follow = true;
		public $class = '';
		public $body_id = '';
        public $main_menu;
        public $css_includes = Array();

		public function __construct() {
			/*$this->main_menu = new Content_Menu($_site, 'main', 'main');
			if ('true' == strToLower($_input['plain'])){
				$this->template = 'plain.php';
                }*/
		}
		
		public function index() {
		}

		public function empty_method() {
			throw new Exception_403();
		}

		public function __call($_function, $_args) {
			throw new Exception_404();
		}
	
	}

?>
