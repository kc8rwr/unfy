<?php

ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

//used by autoloader method so must manually load here
require_once('classes/functions.php');
require_once('classes/unfy.php');
require_once('classes/config.php');
require_once('classes/database.php');
require_once('classes/ustr.php');
require_once('classes/rows.php');
require_once('classes/row.php');

//************************* Autoloader
spl_autoload_register(function($classname){
    $filename = UStr::toLower($classname);
    $filename = UStr::replace('_', DIRECTORY_SEPARATOR, $filename);
    
    $sites = array();
    if (!empty(Unfy::$site)) $sites[] = Unfy::$site;
    $sites[] = 'base';
    
    $found = false;
    
    foreach ($sites as $site){
        $path = "sites/{$site}/models/{$filename}.php";
        if (file_exists($path)){
            $found = true;
            require_once($path);
        }
    }

    if (!$found){
        if (UStr::starts_with($classname, 'rows_', false)){
            $code = "class {$classname} extends Rows {}";
            eval($code);            
        }
        else if (UStr::starts_with($classname, 'row_', false)){
            $code = "class {$classname} extends Row {}";
            eval($code);            
        }
    }
});

//************************** Debugging
$debugConfs = Config::getVal("debug");
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
$input = array_merge( (is_array(@$_SESSION['messages'])?$_SESSION['messages']:array()), $_POST, $_GET);
$_SESSION['messages'] = Array();

$c = $_SERVER['REQUEST_URI'];
if (!UStr::starts_with($c, Config::getval('base_path'))){
    throw new Exception_404();
} else {
    $c = UStr::substr($c, UStr::len(Config::getval('base_path')));
}
hdd($c);

hdd($_SERVER);

$sites = array();
if (null != @Unfy::$site['name']){
    $sites[] = Unfy::$site['name'];
}
$sites[] = 'base';
foreach ($sites as $site){
    $path = "./sites/{$site}/controlers/{$c}.php";
    if (file_exists($path)){
        require_once($path);
        $c = new $c();
        break;
    }
}

hdd($_SERVER['HTTP_ACCEPT_LANGUAGE']);

hdd($c);

?>
