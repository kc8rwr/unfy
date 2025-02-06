<?php

class UStr{

	static function has_mb(){
		return extension_loaded('mbstring');
	}

	static function len($in){
		if (static::has_mb()){
			return mb_strlen($in);
		} else {
			return str_len($in);
		}
	}

	static function starts_with($haystack, $needle, $caseSensitive = true){
		if (static::has_mb()){
			$len_needle = mb_strlen($needle);
			if (0 == $len_needle){
				return true;
			} else {
				if (mb_strlen($haystack) < $len_needle) {
					return false;
				} else {
					if ($caseSensitive) {
						return mb_substr($haystack, 0, $len_needle) == $needle;
					} else {
						return mb_strtolower(mb_substr($haystack, 0, $len_needle)) == mb_strtolower($needle);
					}
				}
			}
		} else if ($caseSensitive) {
			return str_starts_with($haystack, $needle);
		} else {
			return str_starts_with(str_to_lower($haystack), str_to_lower($needle));
		}
	}

	static function ends_with($haystack, $needle, $caseSensitive = true){
		if (static::has_mb()){
			$len_needle = mb_strlen($needle);
			if (0 == $len_needle){
				return true;
			} else {
				if (mb_strlen($haystack) < $len_needle) {
					return false;
				} else {
					if ($caseSensitive) {
						return mb_substr($haystack, mb_strlen($haystack)-(mb_strlen($needle))) == $needle;
					} else {
						return mb_strtolower(mb_substr($haystack, mb_strlen($haystack)-(mb_strlen($needle)))) == mb_strtolower($needle);
					}
				}
			}
		} else if ($caseSensitive) {
			return str_ends_with($haystack, $needle);
		} else {
			return str_ends_with(str_to_lower($haystack), str_to_lower($needle));
		}
	}

	static function toLower($in){
		if (static::has_mb()){
			return mb_strToLower($in);
		} else {
			return strToLower($in);
		}
	}

	static function toUpper($in){
		if (static::has_mb()){
			return mb_strToUpper($in);
		} else {
			return strTUpper($in);
		}
	}

	static function substr($in, $start, $length = null){
		if (static::has_mb()){
			return mb_substr($in, $start, $length);
		} else {
			return substr($in, $start, $length);
		}
	}

	static function replace($search, $replace, $subject, &$count = null){
		//according to comment at https://www.php.net/manual/en/ref.mbstring.php a multibyte implementation is not necessary
		return str_replace($search, $replace, $subject, $count);
	}

	static function jsonify($src, $ident = 0, $indentFirstLine = false){
		$escape = function($in){
			$out = null == $in ? '' : addcslashes($in, '\\\"');
			return $out;
		};
		$stIdent = str_repeat("\t", $ident);
		$output = '';
		if (is_array($src) || $src instanceof ArrayAccess || $src instanceof Traversable){
			foreach($src as $key=> $value){
				if (!is_numeric($value)){
					if (is_array($value) || $value instanceof ArrayAccess || $value instanceof Traversable){
						$value = UStr::jsonify($value, $ident+1, false);
					} else {
						$value = '"'.$escape($value).'"';
					}
				}
				if (!empty($output)){
					$output .= ",\n";
				}
				$output .= "{$stIdent}\t\"{$key}\": {$value}";
			}
			$output = ($indentFirstLine ? $indent : '') . "{\n{$output}\n{$stIdent}}";
		} else {
			$output = empty($src) ? '' : "'" . $escape($src) . "'";
			$output = $stIdent . '{' . $output . '}';
		}
		return $output;
	}

	public static function strpos($haystack, $needle, $offet, $encoding = null){
		$output = null;
		if (static::has_mb()){
			$output =  mb_strpos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strpos($haystack, $needle, $offset, $encoding);
		}
		return $output;
	}

	public static function strrpos($haystack, $needle, $offset = 0, $encoding = null){
		$output = null;
		if (static::has_mb()){
			$output =  mb_strrpos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strrpos($haystack, $needle, $offset);
		}
		return $output;
	}

	public static function contains($haystack, $needle){
		if (function_exists('str_contains')){
			return str_contains($haystack, $needle);
		} else {
			return false !== UStr::strpos($haystack, $needle);
		}
	}
}

?>
