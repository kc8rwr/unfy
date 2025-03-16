<?php

/** 
 * @file
 * @brief The Web Dispatcher.
 *
 * This is the landing file which every web request should land at.
 * It loads the preferences, sets up the autoloader, process the request url to
 * choose the controller, run it, choose the template and insert the controler's
 * output into the template and returns that to the user.
 * It also sets up exception handling.
 */

ini_set('pcre.jit', 0);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

//used by autoloader method so must manually load here
require_once('classes/functions.php');
require_once('classes/unfy.php');
require_once('classes/table.php');
require_once('classes/database.php');
require_once('classes/ustr.php');
require_once('classes/rows.php');
require_once('classes/row.php');
require_once('classes/controler.php');

//*********************** Require Site Overrideable Files

/** 
 * @brief Global function to require site overrideable files.
 *
 * Given the name of a model, view or controler look for it's file first in the
 * current site's respective model, view or controler folder and include it.
 * If the file does not exist in the site's folder try it in the base folder.
 *
 * @param name string - name of file without the .php ending
 * @param type string - type of file, one of controler, model or view
 * 
 * @return bool - true if the file was found, false if not.
 */
function UnfyRequire($name, $type){
	$success = true;
	$name = UStr::toLower($name);
	$type = UStr::toLower($type);
	switch ($type){
		case 'controler':
		case 'model':
		case 'view':
			$type .= 's';
			break;
		default:
			$success = false;
			break;
	}
	if ($success){
		$success = false;
		$sites = array();
		if (null != @Unfy::$site['name']){
			$sites[] = Unfy::$site['name'];
		}
		$sites[] = 'base';
		foreach ($sites as $site){
			$path = UStr::toLower("./sites/{$site}/{$type}/{$name}.php");
			if (file_exists($path)){
				require_once($path);
				$success = true;
				break;
			}
		}
	}
	return $success;
}


/** 
 * @brief Factory method for instantiating Controler objects.
 *
 * Takes the name of the controler without the 'Ctr_' prefix which all
 * Unfy controlers should have. Appends the prefix. Attempts to instantiate it.
 * @param controler string - name of the contoler with the Ctr_ prefix
 *
 * @return object - the controler 
 */
function ControlerFactory($controler){
	if (UnfyRequire($controler, 'controler')){
		$controler = new ('Ctr_'.$controler)();
	}
	return $controler;
}

//************************* Autoloader
spl_autoload_register(function($classname){
	$filename = UStr::toLower($classname);
	$filename = UStr::replace('_', DIRECTORY_SEPARATOR, $filename);

	$found = UnfyRequire($filename, 'model');
	
	if (!$found){
		if (UStr::starts_with($classname, 'rows_', false)){
			if (Database::tableExists(uStr::substr($classname, 5))){
				$code = "class {$classname} extends Rows {}";
				eval($code);
			}
		}
		else if (UStr::starts_with($classname, 'row_', false)){
			if (Database::tableExists(uStr::substr($classname, 4))){
				$code = "class {$classname} extends Row {}";
				eval($code);
			}
		}
		else if (UStr::starts_with($classname, 'table_', false)){
			if (Database::tableExists(uStr::substr($classname, 6))){
				$code = "class {$classname} extends Table {}";
				eval($code);
			}
		}
	}
});

//************************** Debugging
$debugConfs = Unfy::getFig("debug");
if (is_array($debugConfs)){
	foreach ($debugConfs AS $key=> $value){
		switch ($key)
		{
			case 'display_errors' :
				ini_set('display_errors', 1 == $value ? '1' : '0');
				break;
			case 'display_startup_errors' :
				ini_set('display_errors', 1 == $value ? '1' : '0');
				break;
			case 'error_reporting' :
				if (is_int($value)){
					error_reporting($value);
				}
				break;
		}
	}
}

Unfy::$site = @(new Rows_Site(array('domain'=>$_SERVER['SERVER_NAME'])))[0];

/*
//************************* Exception Handler
function exception_handler($_exception) {
$c = new ExceptionC($site, $config, $input, 'exception'.DS.'e'.$_exception->getCode(), $logins);
$m = 'E'.$_exception->getCode();
$c->$m($_exception->getMessage());
		
if ($_SESSION['isMobile']) {
$viewPath = $site.DS.'views'.DS.'mobile'.DS.$c->view.'.php';
if (!file_exists($viewPath)) {
$viewPath = $site.DS.'views'.DS.$c->view.'.php';
if (!file_exists($viewPath)) {
$viewPath = 'base'.DS.'views'.DS.'mobile'.DS.$c->view.'.php';
if (!file_exists($viewPath)) {
$viewPath = 'base'.DS.'views'.DS.$c->view.'.php';
if (!file_exists($viewPath)) {
$viewPath = $site.DS.'views'.DS.'mobile'.DS.'exceptions/unknown.php';
if (!file_exists($viewPath)) {
$viewPath = $site.DS.'views'.DS.'exceptions/unknown.php';
if (!file_exists($viewPath)) {
$viewPath = 'base'.DS.'views'.DS.'mobile'.DS.'exceptions/unknown.php';
if (!file_exists($viewPath)) {
$viewPath = 'base'.DS.'views'.DS.'exceptions/unknown.php';
}
}
}
}
}
}
}
} else {
$viewPath = $site.DS.'views'.DS.$c->view.'.php';
if (!file_exists($viewPath)) {
$viewPath = 'base'.DS.'views'.DS.$c->view.'.php';
if (!file_exists($viewPath)) {
$viewPath = $site.DS.'views'.DS.'exceptions/unknown.php';
if (!file_exists($viewPath)) {
$viewPath = 'base'.DS.'views'.DS.'exceptions/unknown.php';
}
}
}
}
if (!file_exists($viewPath)) {
die ('Exception Handler Cannot Find View');
}
		
$template_path = $site.DS.'templates'.DS.$c->template;
if (!file_exists($template_path)) {
$template_path = 'base'.DS.'templates'.DS.$c->template;
}

include $template_path;
		
}
set_exception_handler('exception_handler');
*/

session_start();
/** 
 * @brief A global variable which holds get/post input and messages from the previous page.
 * 
 * This is an associative array containing GET, and POST messages as well as any key/value pairs
 * which were placed in an associative array at $_SESSION['messages'] by the previous run for this
 * session. If two or more of these contain elements with the same key the preferences is in that
 * order. GET overrides all and POST overrides $_SESSION['messages'].
 *
 * This may be used primarily for form interactions. Get overrides set because this makes for
 * easier troubleshooting. A developer or support person can test different values by simply
 * editing the url.  Some developers do not like allowing for GET as they see it to be less secure
 * since in normal operation a user does not see the actual POST data they are submitting in order
 * to be able to enter values that are outside limits, etc...  I disagree as it is still possible
 * for an advanced user to change what is being sent in POST data by various means such as browser
 * extensions or even simply re-writing the form in a local file. Disallowing GET therefore is fake
 * security and all submitted data must always be checked and validated server-side to be safe w/
 * or w/out GET.
 *
 * $_SESSION['messages'] is used then when server-side checking fails the user input. It is used to
 * send the user input back to the form so that the user does not have to re-enter everything. It
 * is also used to send the error messages which will be displayed on the form so that the user
 * knows what is to be fixed.
 *
 */
$input = array_merge( (is_array(@$_SESSION['messages'])?$_SESSION['messages']:array()), $_POST, $_GET);

/** 
 * @brief An associative array to place 'messages' which should be available upon the next page
 * load for this session.
 *
 * Upon every page load the contents of $_SESSION['messages'] are removed and copied into the
 * $input global variable. This is primarily used for a user's previous form entires and input
 * error messages when a form submission fails server-side validation.
 */
$_SESSION['messages'] = Array();

$cols = Table_test::getColumns();
hdd(Table_test::toString());


$c = 'Base';
$args = $_SERVER['REQUEST_URI'];
if (!UStr::starts_with($args, Unfy::getFig('base_path'))){
	throw new Exception_404();
} else {
	$args = UStr::substr($args, UStr::len(Unfy::getFig('base_path')));
}
while (UStr::ends_with($args, '/')){
	$args = UStr::substr($args, 0, UStr::len($args)-1);
}
$args = explode('/', $args);
if (0 < count($args)){
	$c = array_shift($args);
}

if (UnfyRequire($c, 'controler')){
	$c = new ('Ctr_'.$c);
} else {
	array_unshift($args, (is_string($c) ? $c : get_class($c)) );
	if (UnfyRequire('Base', 'controler')){
		$c = new Ctr_Base();
	}
}

if (!UnfyRequire($c->view, 'view')){
	throw new Exception_404();
}

?>
