<?php

class Unfy{

	public static $site = null;


	/*****************************************
	  Config
	****************************************/
	
	private static $ini_contents = null;
    
	private static function initConfig(){
		if (null == static::$ini_contents){

			$ini_file = 'unfy.ini';
			$template_ini_file = 'unfy.ini.template';

			//ensure the config file template exists
			if (!file_exists($template_ini_file)){
				file_put_contents($template_ini_file, '');
			}
			//ensure the config file exists
			if (!file_exists($ini_file)){
				copy($template_ini_file, $ini_file);
			}
			$ini_contents = parse_ini_file($ini_file, true, INI_SCANNER_TYPED);

			//normalize base path
			$ini_contents['base_path'] = empty($ini_contents['base_path']) ? '' : $ini_contents['base_path'];
			$ini_contents['base_path'] = trim($ini_contents['base_path']);
			$ini_contents['base_path'] = rtrim($ini_contents['base_path'], DIRECTORY_SEPARATOR);
			$ini_contents['base_path'] .= DIRECTORY_SEPARATOR;

			//normalize db path
			if (!(array_key_exists('database', $ini_contents) && is_array($ini_contents['database']))){
				$ini_contents['database'] = Array();
			}
			if (!(array_key_exists('path', $ini_contents['database']) && is_string($ini_contents['database']['path']))){
				$ini_contents['database']['path'] = '';
			}
			$ini_contents['database']['path'] = trim($ini_contents['database']['path']);
			if (uSTr::starts_with('.'.DIRECTORY_SEPARATOR, $ini_contents['database']['path'])){
				$ini_contents['database']['path'] = uSTr::substr($ini_contents['database']['path'], 2);
			}
			static::$ini_contents = $ini_contents;

		}
	}

	public static function getFig(){
		static::initConfig();
		$bucket = static::$ini_contents;
		foreach (func_get_args() AS $arg){
			if (array_key_exists($arg, $bucket)){
				$bucket = $bucket[$arg];
			} else {
				return null;
			}
		}
		return $bucket;
	}

	

	/*****************************************
	  Cache
	****************************************/

	private static $cacheEnabled = null;
	private static $cachePrefix = 'unfy_';
	private static $cache = array();
	private static $hasAPCu = null;
	private static $memcached = null;
	private const MAX_CACHE_TTL_MIN = 60;
	private const MIN_CACHE_TTL_MIN = 15;

	
	public static function getCache($key){
		$output = null;
		if (!array_key_exists($key, static::$cache)){
			static::initializeCache();
			if (static::$cacheEnabled){
				$cache_key = static::$cachePrefix.$key;
				if (static::$hasAPCu){
					if (apcu_exists($cache_key)){
						static::$cache[$key] = apcu_fetch($cache_key);
					}
				} else if (null != static::$memcached) {
					$mcResponse = static::$memcached->get($cache_key);
					if (false !== $mcResponse){
						static::$cache[$key] = $mcResponse;
					}
				}
			}
		}
		if (array_key_exists($key, static::$cache)){
			$output = static::$cache[$key];
		}
		return $output;
	}

	public static function setCache($key, $value){
		static::$cache[$key] = $value;
		static::initializeCache();
		if (static::$cacheEnabled){
			$cache_key = static::$cachePrefix.$key;
			if (static::$hasAPCu){
				apcu_store($cache_key, $value, rand(static::MIN_CACHE_TTL_MIN, static::MAX_CACHE_TTL_MIN)*60);
			} else if (null !== static::$memcached){
				static::$memcached->set($cache_key, $value, rand(static::MIN_CACHE_TTL_MIN, static::MAX_CACHE_TTL_MIN)*60);
			}
		}
	}
	
	private static function initializeCache(){
		if (null == static::$cacheEnabled){
			//get prefix
			$cacheConfig = Unfy::getFig("cache");
			if (null != $cacheConfig && is_array($cacheConfig)){
				static::$cacheEnabled = false;
				if (array_key_exists('enabled', $cacheConfig)){
					static::$cacheEnabled = $cacheConfig['enabled'];
				}
				if (static::$cacheEnabled && array_key_exists('prefix', $cacheConfig) && !empty($cacheConfig['prefix'])){
					static::$cachePrefix = $cacheConfig['prefix'];
				}
			}
			if (static::$cacheEnabled){
				//check for APCu
				if (null === static::$hasAPCu){
					static::$hasAPCu = function_exists('apcu_enabled') && apcu_enabled();
				}
				//prepare memcached
				if ((!static::$hasAPCu) && null === static::$memcached){
					$host = "127.0.0.1";
					$port = 11211;
					$use_UDP = false;
					if (null != $cacheConfig && is_array($cacheConfig)){
						if (array_key_exists('host', $cacheConfig) && !empty($cacheConfig['host'])){
							$host = $cacheConfig['host'];
						}
						if (array_key_exists('port', $cacheConfig) && !empty($cachsConfig['port'])){
							$port = $cachConfig['port'];
						}
						if (array_key_exists('use_udp', $cacheConfig) && !empty($cacheConfig['use_udp'])){
							$use_UDP = $memcachedConfig['use_udp'] ? true : false;
						}
					}
					if (class_exists("Memcached")){
						$mc = new Memcached();
						$mc->setOption(Memcached::OPT_USE_UDP, $use_UDP);
						$mc->addServer($host, $port);
						static::$memcached = $mc;
					}
				}
			}
		}
	}
}

?>
