<?php

UStr::$has_mb = extension_loaded('mbstring');

/** 
 * @brief Unfy String class.
 * 
 * This is a class containing static methods for manipulating strings. It should mirror PHP's own
 * string functions. The purpose of this class is that when the mbstrings extension is available
 * it should default to using it. However when it is not it should gracefully fail back to
 * the standard non-multibyte built in PHP string functions.
 *
 * For the most part methods should take the same arguments and have the same behaviors as their
 * standard function counterparts. There may be some cases where optional arguments are added to
 * enable additional functionality.
 *
 * For ease of use methods will are basically named the same as their original PHP function
 * counterparts. However for increased constency and also for brevity (since the user will be
 * prefixing all calls to these methods with 'UStr::' anyway) any names beginning with the
 * str or str_ prefixes will not include this part.
 *
 * Wrappers for some string functions which already worked with multibyte strings are included just for ease of use.
 */
class UStr{

	public static $has_mb = null;
	
	/// A multibyte string containing known 'whitespace' characters, used by trim, ltrim, rtrim methods
	const MB_SPACE_NEEDLE = " \n\r\t\v\f\x00\u{00A0}\u{1680}\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}\u{2008}\u{2009}\u{200A}\u{2028}\u{2029}\u{202F}\u{205F}\u{3000}\u{0085}\u{180E}";
	const MB_WORD_SEPARATOR = " \n\r\t\v\f\x00\u{00A0}\u{1680}\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}\u{2008}\u{2009}\u{200A}\u{2028}\u{2029}\u{202F}\u{205F}\u{3000}\u{0085}\u{180E}=+*/\\.;:[]{}()<>&%$@#^!?,~";
	/// A byte string containing known 'whitespace' characters, used by trim, ltrim, rtrim methods
	const SB_SPACE_NEEDLE = " \n\r\t\v";
	const CASE_LOWER = MB_CASE_LOWER;
	const CASE_UPPER = MB_CASE_UPPER;
	const CASE_TITLE = MB_CASE_TITLE;
	const CASE_FOLD = MB_CASE_FOLD;
	const CASE_LOWER_SIMPLE = MB_CASE_LOWER_SIMPLE;
	const CASE_UPPER_SIMPLE = MB_CASE_UPPER_SIMPLE;
	const CASE_TITLE_SIMPLE = MB_CASE_TITLE_SIMPLE;
	const CASE_FOLD_SIMPLE = MB_CASE_FOLD_SIMPLE;
	
	/** 
	 * Quote string with slashes in a C style.
	 *
	 * @param $string The string to be escaped.
	 * @param $characters A list of characters to be escaped. Can include ranges using '..'.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @warning Because multibyte characters in some character sets may contain the backslash byte addcslashes() is not a safe way to prevent sql injection. Use the correct method which is made for this purpose such as PDO::quote or mysqli::real_escape_string.
	 *
	 * @return Returns the escaped string.
	 */
	public static function addcslashes(string $string, string $characters, ?string $encoding=null):string {
		if (static::$has_mb){
			$characters = static::toCharList($characters, $encoding);
			$output = $string;
			if (in_array('\\', $characters)){ //escape backslashes first if that is in the list so that added backslashes do not get escaped
				$output = static::replace('\\', '\\\\', $output);
			}
			foreach ($characters AS $char){
				if ('\\' != $char){
					$output = static::replace($char, '\\'.$char, $output);
				}
			}
			return $output;
		} else {
			return addcslashes($string, $characters);
		}
	}

	/** 
	 * @brief Quote string with slashes.
	 * Returns a string with backslashes added before characters that need to be escaped. These characters are:
	 * - single quote (')
	 * - double quote (")
	 * - backslash (\)
	 * - NULL (the NULL byte)
	 *
	 * @warning Not secure for escaping sql strings. Use the correct PDO escape function for your database type, mysql_real_escape_string or other apropriate method for your database type.
	 * @param $string The input string.
	 * @param $encoding Not used for this method, only there for consistency.
	 * 
	 * @return The escaped string.
	 */
	public static function addslashes(string $string, ?string $encoding=null):string {
		return addslashes($string);
	}
	
	/** 
	 * Binary safe case-insensitive string comparison.
	 * 
	 * @param $string1 The first string.
	 * @param $string2 The second string.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns a value less than 0 if string1 is less than string2; a value greater than 0 if string1 is greater than string2, and 0 if they are equal. No particular meaning can be reliably inferred from the value aside from its sign.
	 */
	public static function casecmp(string $string1, string $string2, ?string $encoding=null):int {
		if (static::$has_mb){ 
			//from https://www.php.net/manual/en/function.strcasecmp.php comment by chris at cmbuckley dot co dot uk
			$encoding = null == $encoding ? mb_internal_encoding() : $encoding;
			return strcmp(mb_strtoupper($string1, $encoding), mb_strtoupper($string2, $encoding));
		} else {
			return strcasecmp($string1, $string2);
		}
	}

	/** 
	 *  Check if strings are valid for the specified encoding.
	 * 
	 * @param $value The byte stream or array to check.
	 * @param $encoding The expected encoding.
	 * 
	 * @return Returns true on success or false on failure.
	 */
	public static function check_encoding(array|string|null $value = null, ?string $encoding = null): bool {
		if (static::$has_mb){
			return mb_check_encoding($value, $encoding);
		} else {
			return true;
		}
	}
	
	/** 
	 * Return character by Unicode code point value.
	 * 
	 * @param $in The code point.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return string The character.
	 */
	public static function chr(int $in, ?string $encoding=null): string {
		if (static::$has_mb){
			return mb_chr($in, $encoding);
		} else {
			return chr($in);
		}
	}

	/** 
	 * Binary safe string comparison.
	 * 
	 * @param $string1 The first string.
	 * @param $string2 The second string.
	 * @param $encoding Not used for this method, only there for consistency.
	 * 
	 * @return Returns a value less than 0 if string1 is less than string2; a value greater than 0 if string1 is greater than string2, and 0 if they are equal. No particular meaning can be reliably inferred from the value aside from its sign.
	 */
	public static function cmp(string $string1, string $string2, ?string $encoding=null){
		return (strcmp($string1, $string2));
	}
	
	/** 
	 * Binary safe string comparison using current locale.
	 * 
	 * @param $string1 The first string.
	 * @param $string2 The second string.
	 * @param $encoding Not used for this method, only there for consistency.
	 * 
	 * @return Returns a value less than 0 if string1 is less than string2; a value greater than 0 if string1 is greater than string2, and 0 if they are equal. No particular meaning can be reliably inferred from the value aside from its sign.
	 */
	public static function coll(string $string1, string $string2, ?string $encoding=null){
		return (strcoll($string1, $string2));
	}
	
	/** 
	 * @brief Split up a string by length by inserting a separating string.
	 *
	 * Can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode() output to match RFC 2045 semantics.
	 * It inserts separator every length characters.
	 * @param $string The string to be chunked.
	 * @param $length The chunk length.
	 * @param $separator The line endig sequence.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return string - The chunked output string.
	 */
	public static function chunk_split(string $string, int $length=76, string $separator="\r\n", ?string $encoding=null): string {
		if (static::$has_mb){
			return static::implode($separator, static::split($string, $length, $encoding));
		} else {
			return chunk_split($string, $length, $separator);
		}
	}

	/** 
	 * Determine if a string contains a given substring.
	 * 
	 * @param $haystack The string to search in.
	 * @param $needle The substring to search for.
	 * @param $caseSensitive Should the search be case sensitive?
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return bool - Does the haystack string contain the needle string.
	 */
	public static function contains(string $haystack, string $needle, bool $caseSensitive=true, ?string $encoding=null):bool {
		if (!$caseSensitive){
			return static::contains(Static::tolower($haystack), Static::tolower($needle), $encoding);
		}
		if (function_exists('str_contains')){
			return str_contains($haystack, $needle);
		} else {
			return false !== UStr::strpos($haystack, $needle, $encoding);
		}
	}

	/** 
	 * @brief Perform case folding on a string.
	 *
	 * Performs case folding on a string, converted in the way specified by mode.
	 * @param $string The string being converted.
	 * @param $mode The mode of the conversion. It can be one of:
	 * - UStr::CASE_UPPER (convert to all-uppercase)
	 * - UStr::CASE_LOWER (convert to all-lowercase)
	 * - UStr::CASE_TITLE (uppercase first char of each word)
	 * - UStr::CASE_FOLD
	 * - UStr::CASE_UPPER_SIMPLE
	 * - UStr::CASE_LOWER_SIMPLE
	 * - UStr::CASE_TITLE_SIMPLE
	 * - UStr::MB_CASE_FOLD_SIMPLE
	 * @note With MB implementation Simple always tramslates chars 1-1 where as with some specific characters in some languages mappings might not be 1-1. ex) upper of ß = SS vs ẞ. CASE_FOLD is basically
	 * uppercasing except for exceptions in some languages such that the single-bit implementation is just uppercasing.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return 
	 */
	public static function convert_case(string $string, int $mode, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_convert_case($string, $mode, $encoding);
		} else {
			switch ($mode){
				case CASE_LOWER:
				case CASE_LOWER_SIMPLE:
					return strtolower($string);
					break;
				case CASE_UPPER:
				case CASE_FOLD:
				case CASE_UPPER_SIMPLE:
				case CASE_FOLD_SIMPLE:
					return strtoupper($string);
					break;
				case CASE_TITLE:
				case CASE_TITLE_SIMPLE:
					return ucwords($string);
					break;
				default:
					return false;
					break;
			}
		}
	}

	/** 
	 * Find length of initial segment not matching characters in mask.
	 * 
	 * @param $string The string to examine.
	 * @param $characters The string containing every disallowed character.
	 * @param $offset The position in string to start searching.
	 * - If offset is given and is non-negative, then strcspn() will begin examining string at the offset'th position. For instance, in the string 'abcdef', the character at position 0 is 'a', the character at position 2 is 'c', and so forth.
	 * - If offset is given and is negative, then strcspn() will begin examining string at the offset'th position from the end of string.
	 * @param $length The length of the segment from string to examine.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 * - If length is given and is non-negative, then string will be examined for length characters after the starting position.
	 * - If length is given and is negative, then string will be examined from the starting position up to length characters from the end of string. 
	 *
	 * @return Returns the length of the initial segment of string which consists entirely of characters not in characters. 
	 */
	public static function cspn(string $string, string $characters, int $offset=0, ?int $length=null, ?string $encoding=null): int {
		if (static::$has_mb){
			if (0 < $offset || null != $length){
				return static::cspn(static::substr($string, $offset, $length), $characters, 0, null, $encoding);
			}
			$output = static::len($string);
			$arChars = static::split($characters);
			$arChars = array_unique($arChars);
			foreach($arChars as $char){
				$pos = min(static::pos($string, $char, 0, $encoding));
				if (false !== $pos){
					if (0 == $pos) return 0;
					$output = $pos;
					$string = static::substr($string, 0, $pos+1);
				}
			}
			return $output;
		} else {
			return strcspn($string, $characters, $offset, $length);
		}
	}

	/** 
	 * Decrements a numeric or alphanumeric string.
	 * 
	 * @param $string The string to be decremented.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return The decremented string.
	 * @throws ValueError if character is empty or is not found within a known alphabet, number of ideographic block
	 */
	public static function decrement(string $string, ?string $encoding=null):string{
		if (0 == Static::len($string, $encoding)){
			throw new ValueError('Argument #1 ($string) cannot be empty');
		}
		$block = null;
		$borrow = true;
		$output = '';
		for ($i = static::len($string, $encoding) - 1; $i > -1; $i--){
			$char = static::substr($string, $i, 1, $encoding);
			$charnum = static::ord($char, $encoding);
			if (is_null($block) || $charnum < $block->start || $charnum > $block->end){
				$block = UCharBlock::FindAlpha($charnum);
			}
			if (null == $block || ($block->type != 'alpha' && $block->type != 'number')){
				throw new ValueError('Argument #1 ($string) must consist entirely of characters from alphanumeric blocks.');
			}
			if ($borrow){
				$borrow = false;
				$charnum--;
				if ($charnum < $block->start){
					$borrow = true;
					$charnum = $block->end;
				}
				$output = static::chr($charnum, $encoding) . $output;
			} else {
				$output = $char . $output;
			}
		}
		if ($borrow || ('number' == $block->type && static::ord($output, $encoding)) == $block->start){
			$output = static::substr($output, 1, null, $encoding);
		}
		return $output;
	}

	/** 
	 * Set/Get character encoding detection order.
	 * 
	 * @param $encoding encoding is an array or comma separated list of character encoding. If encoding is omitted or null, it returns the current character encoding detection order as array.
	 *
	 * @note This setting affects mb_detect_encoding() and mb_send_mail().
	 *
	 * @return When setting the encoding detection order, true is returned on success or false on failure. When getting the encoding detection order, an ordered array of the encodings is returned.
	 */
	public static function detect_order(array|string|null $encoding = null):array|bool {
		if (static::$has_mb){
			return mb_detect_order($encoding);
		} else {
			if (is_null($encoding)){
				return array('ISO-8859-1');
			} else {
				if (is_array($encoding) && 1 == count($encoding)){
					$encoding = $encoding[0];
				}
				return is_string($encoding) && 'ISO_8859_1' == static::toupper($encoding);
			}
		}
	}
	
	/** 
	 * Does the haystack string end with the contents of the needle string?
	 * 
	 * @param $haystack The string to check the end of.
	 * @param $needle The ending to look for.
	 * @param $caseSensitive False to ignore case.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 *
	 * @return bool
	 */
	public static function ends_with(string $haystack, string $needle, bool $caseSensitive=true, ?string $encoding=null):bool {
		if (!$caseSensitive){
			return static::ends_with(static::tolower($haystack, $encoding), static::tolower($needle, $encoding), true);
		}
		if (function_exists('str_ends_with')){
			return str_ends_with($haystack, $needle);
		} else {
			$len_needle = static::len($needle, $encoding);
			if (0 == $len_needle){
				return true;
			} else {
				if (static::len($haystack, $encoding) < $len_needle) {
					return false;
				} else {
					return static::substr($haystack, -$len_needle, encoding: $encoding) == $needle;
				}	
			}
		}
	}

	/** 
	 * Split a string by a string.
	 * 
	 * @param $separator The boundary string.
	 * @param $string The input string.
	 * @param $limit Limit the results.
	 * - If limit is set and positive, the returned array will contain a maximum of limit elements with the last element containing the rest of string.
	 * - If the limit parameter is negative, all components except the last -limit are returned.
	 * - If the limit parameter is zero, then this is treated as 1.
	 * @param $encoding Not currently used, just there for consistency.
	 *
	 * @return 
	 */
	public static function explode(string $separator, string $string, int $limit=PHP_INT_MAX, ?string $encoding=null):array {
		return explode($separator, $string, $limit);
	}

	/** 
	 * Get internal settings of mbstring.
	 * 
	 * @param $type Which setting to return, not specified or 'all' to return an array of all the settings.
	 * - internal_encoding
	 * - http_input
	 * - http_output
	 * - http_output_conv_mimetypes
	 * - mail_charset
	 * - mail_header_encoding
	 * - mail_body_encoding
	 * - illegal_chars
	 * - encoding_translation
	 * - language
	 * - detect_order
	 * - substitute_character
	 * - strict_detection
	 * 
	 * @return An array of type information if type is not specified, otherwise a specific type, or false on failure.
	 */
	public static function get_info(string $type = "all"):array|string|int|false|null { //FIXME
		if (static::$has_mb){
			return mb_get_info($type);
		} else {
			switch (static::tolower($type)){
				case 'all':
					$keys = array(
						'internal_encoding',
						'http_output',
						'http_output_conv_mimetypes',
						'mail_charset',
						'mail_header_encoding',
						'mail_body_encoding',
						'illegal_chars',
						'encoding_translation',
						'language',
						'detect_order',
						'substitute_character',
						'strict_detection',
					);
					$output = array();
					foreach ($keys AS $key){
						$output[$key] = static::get_info($key);
					}
					return $output;
					break;
				case 'detect_order':
					return static::detect_order();
					break;
				case 'encoding_translation':
					return 'Off';
					break;
				case 'http_input':
					return static::http_input();
					break;
				case 'http_output':
					return static::http_output();
					break;
				case 'http_output_conv_mimetypes':
					return 'a^';
					break;
				case 'illegal_chars':
					return 0;
					break;
				case 'internal_encoding':
					return static::internal_encoding();
					break;
				case 'language':
					return static::language();
					break;
				case 'mail_charset':
					return 'ISO-8859-1';
					break;
				case 'mail_body_encoding':
				case 'mail_header_encoding':
					return 'BASE64';
					break;
				case 'strict_detection':
					return 'Off';
					break;
				case 'substitute_character':
					return static::substitute_character();
					break;
				default:
					return false;
					break;
			}
		}
	}
	
	/** 
	 * @brief Convert HTML entities to their corresponding characters.
	 *
	 * More precisely, this function decodes all the entities (including all numeric entities) that a) are necessarily valid for the chosen document type — i.e., for XML,
	 * this function does not decode named entities that might be defined in some DTD — and b) whose character or characters are in the coded character set associated with
	 * the chosen encoding and are permitted in the chosen document type. All other entities are left as is.
	 * @param $string The input string.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes and which document type to use.
	 * The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 *
	 * @return 
	 */
	public static function html_entity_decode(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, ?string $encoding=null): string {
		return html_entity_decode($string, $flags, $encoding);
	}

	/** 
	 * @brief Convert all applicable characters to HTML entities.
	 * This function is identical to htmlspecialchars() in all ways, except with htmlentities(), all characters which have HTML character entity equivalents are translated into these entities. The get_html_translation_table() function can be used to return the translation table used dependent upon the provided flags constants.
	 *
	 * @note If you want to decode instead (the reverse) you can use Ustr::html_entity_decode().
	 * @param $string The input string.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes, invalid code unit sequences and the used document type.  The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401. 
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 * @param $double_encode When double_encode is turned off PHP will not encode existing html entities. The default is to convert everything.
	 *
	 * @return 
	 */
	public static function htmlentities(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, ?string $encoding=null, bool $double_encode=true):string {
		 return htmlentities($string, $flags, $encoding, $double_encode);
	 }

	/** 
	 * @brief Convert special characters to HTML entities.
	 *
	 * Certain characters have special significance in HTML, and should be represented by HTML entities if they are to preserve their meanings.
	 * This function returns a string with these conversions made
	 * @note If you require all input substrings that have associated named entities to be translated, use htmlentities() instead.
	 * @param $string The string to be converted.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes, invalid code unit sequences and the used document type. The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401.
	 * @param $double_encode When double_encode is turned off PHP will not encode existing html entities, the default is to convert everything.
	 * @param $encoding An optional argument defining the encoding used when converting characters.
	 *
	 * @return The converted string
	 */
	public static function htmlspecialchars(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, bool $double_encode=true, ?string $encoding=null):string {
		return htmlspecialchars($string, $flags, $encoding, $double_encode);
	}

	/** 
	 * Convert special HTML entities back to characters
	 *
	 * @note This function is the opposite of htmlspecialchars().
	 * @param $string The string to decode.
	 * @param $flags A bitmask of one or more of the following flags, which specify how to handle quotes and which document type to use.
	 * The default is ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401.
	 * @param $encoding Not currently used, present for consistency.
	 *
	 * @return The decoded string.
	 */
	public static function htmlspecialchars_decode(string $string, int $flags=ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, ?string $encoding=null):string {
		return htmlspecialchars_decode($string, $flags);
	}

	/** 
	 * Detect HTTP input character encoding
	 * 
	 * @param $type Input string specifies the input type. "G" for GET, "P" for POST, "C" for COOKIE, "S" for string, "L" for list, and "I" for the whole list (will return array). If type is omitted, it returns the last input type processed.
	 * 
	 * @return The character encoding name, as per the type, or an array of character encoding names, if type is "I". If mb_http_input() does not process specified HTTP input, it returns false.
	 */
	public static function http_input(?string $type=null):array|string|false {
		if (static::$has_mb){
			return mb_http_input($type);
		} else {
			$type = null == $type ? static::$http_input_last_type : $type;
			static::$http_input_last_type = $type;
			switch ($type){
				case 'I':
				case 'i':
					return array('ISO-8859-1');
					break;
				case 'L':
				case 'l':
					return 'ISO-885901';
					break;
				case 'C':
				case 'c':
				case 'G':
				case 'g':
				case 'P':
				case 'p':
				case 'S':
				case 's':
					return false;
					break;
				default:
					throw new ValueError('Uncaught ValueError: UStr::http_input(): Argument #1 ($type) must be one of "G", "P", "C", "S", "I"');
					break;
			}				
		}
	}

	/** 
	 * Stores the last type used by http_input.
	 */
	private static $http_input_last_type = "I";

	/** 
	 * @brief Set/Get HTTP output character encoding.
	 * Set/Get the HTTP output character encoding. Output after this function is called will be converted from the set internal encoding to encoding.
	 *
	 * @param $encoding 
If encoding is set, mb_http_output() sets the HTTP output character encoding to encoding. If encoding is omitted, mb_http_output() returns the current HTTP output character encoding.
	 * 
	 * @return If encoding is omitted, mb_http_output() returns the current HTTP output character encoding. Otherwise, Returns true on success or false on failure.
	 */
	public static function http_output(?string $encoding = null):string|bool {
		if (static::$has_mb){
			return mb_http_output($encoding);
		} else {
			if (null == $encoding){
				return 'ISO-8859-1';
			} else if ('ISO-8859-1' == static::toupper($encoding)) {
				return true;
			}
			return false;
		}
	}
	
	/** 
	 * @brief Join array elements with a string.
	 * 
	 * Join array Elements with a string.
	 * @param $separator The separator string. Default is ','
	 * @param $array The array of strings to implode.
	 * @param $encoding Not currently used, present for consistency.
	 *
	 * @return 
	 */
	public static function implode(string|array $separator, array $array=null, ?string $encoding=null):string {
		if (null == $array && is_array($separator)){
			$array = $separator;
			$separator = ',';
		}
		return implode($separator, $array);
	}

	/** 
	 * Increments a numeric or alphanumeric string.
	 * 
	 * @param $string The string to be incremented.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return The incremented string.
	 * @throws ValueError if character is empty or is not found within a known alphabet, number of ideographic block
	 */
	public static function increment(string $string, ?string $encoding=null):string{
		if (0 == Static::len($string, $encoding)){
			throw new ValueError('Argument #1 ($string) cannot be empty');
		}
		$block = null;
		$carry = true;
		$output = '';
		for ($i = static::len($string, $encoding) - 1; $i > -1; $i--){
			$char = static::substr($string, $i, 1, $encoding);
			$charnum = static::ord($char, $encoding);
			if (is_null($block) || $charnum < $block->start || $charnum > $block->end){
				$block = UCharBlock::FindAlpha($charnum);
			}
			if (null == $block || ($block->type != 'alpha' && $block->type != 'number')){
				throw new ValueError('Argument #1 ($string) must consist entirely of characters from alphanumeric blocks.');
			}
			if ($carry){
				$carry = false;
				$charnum++;
				if ($charnum > $block->end){
					$carry = true;
					$charnum = $block->start;
				}
				$output = static::chr($charnum, $encoding) . $output;
			} else {
				$output = $char . $output;
			}
		}
		if ($carry && !is_null($block)){
			$charnum = $block->start;
			if ('number' == $block->type){
				$charnum++;
			}
			$output = static::chr($charnum, $encoding) . $output;
		}
		return $output;
	}
	
	/** 
	 * Set/Get internal character encoding.
	 * 
	 * @param $internal_encoding The character encoding name used for the HTTP input character encoding conversion, HTTP output character encoding conversion, and the default character encoding for string functions defined by the mbstring module.
	 *
	 @note You should notice that the internal encoding is totally different from the one for multibyte regex.
	 * 
	 * @return If encoding is set, then Returns true on success or false on failure. In this case, the character encoding for multibyte regex is NOT changed. If encoding is omitted, then the current character encoding name is returned.
	 */
	public static function internal_encoding(?string $encoding = null):string|bool {
		if (static::$has_mb){
			return mb_internal_encoding($encoding);
		} else {
			if (null == $encoding){
				return 'ISO-8859-1';
			} else if ('ISO-8859-1' == static::toupper($encoding)) {
				return true;
			}
			return false;
		}
	}
	
	/** 
	 * Find the position of the first occurrence of a case-insensitive substring in a string
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - If specified, search will start this number of characters counted from the beginning of the string. If the offset is negative, the search will start this number of characters counted from the end of the string.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return Position number or false if not found as an int.
	 */
	public static function ipos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = false;
		if (static::$has_mb){
			$output =  mb_stripos($haystack, $needle, $offset, $encoding);
		} else {
			$output = stripos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * Replace all occurrences of the case insensitive search string with the replacement string
	 * 
	 * @param $search - The string segment that is to be replaced.
	 * @param $replace - The segment that is to replace the search segment.
	 * @param $subject - The input string.
	 * @param $&count - If passed, this will be set to the number of replacements performed.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return string
	 */
	public static function ireplace(array|string $search, array|string $replace, string|array $subject, int &$count=null, ?string $encoding=null){
		if (false && static::$has_mb){
			$count = 0;
			$search = is_array($search) ? $search : array($search);
			$replace = is_array($replace) ? $replace : array_fill(0, count($search), $replace);
			while (count($replace) < count($search)){
				$replace[] = '';
			}
			for ($i = 0; $i < count($search); $i++){
				$st_search = $search[$i];
				$st_replace = $replace[$i];
				$len_replace = len($st_replace);
				$pos = 0;
				while (false !== $pos = Ustr::ipos($subject, $st_search, $pos, $encoding)){
					$new = '';
					if (0 < $pos){
						$new .= substr($subject, 0, $pos);
					}
					$new .= $st_replace;
					$new .= substr($subject, $pos + $len_replace);
					$subject = $new;
					$pos .= $len_replace; //prevents infinite loop with different case of same substring 
				}
			}
		} else {
			return str_ireplace($search, $replace, $subject, $count);
		}
	}

	/** 
	 * Is a string all whitespace characters?
	 * 
	 * @param $string The string to check.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.	 
	 * 
	 * @return Returns true if the string is empty or all whitespace, otherwise false.
	 */
	public static function is_whitespace(string $string, ?string $encoding=null):bool {
		$whitespace = static::SB_SPACE_NEEDLE;
		if (static::$has_mb){
			$whitespace = static::MB_SPACE_NEEDLE;
		}
		$arString = static::toCharList($string, $encoding);
		foreach ($arString AS $char){
			if (!static::contains($whitespace, $char, true, $encoding)){
				return false;
			}
		}
		return true;
	}
	
	/** 
	 * Recursively convert an object to a json string.
	 * 
	 * @param $src - The source object.
	 * @param $indent - Indent each line by this many characters.
	 * @param $indentFirstLine Should the first line be indented (if indent > 0)
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.	 
	 *
	 * @return 
	 */
	public static function jsonify($src, int $indent=0, bool $indentFirstLine=false, ?string $encoding=null):string {
		$escape = function($in){
			$out = $in;
			if (($out instanceof \UnitEnum || $out instanceof \BackedEnum)){
				$out = $out->name;
			}
			$out = null == $out ? '' : addcslashes($out, '\\\"');
			return $out;
		};
		$stIndent = str_repeat("\t", $indent);
		$output = '';
		if (is_array($src) || $src instanceof ArrayAccess || $src instanceof Traversable){
			foreach($src as $key=> $value){
				if (!is_numeric($value)){
					if (is_array($value) || $value instanceof ArrayAccess || $value instanceof Traversable){
						$value = UStr::jsonify($value, $indent+1, false);
					} else {
						if (is_object($value) && method_exists($value, '__toString')){
							$value = $value->__toString($indent+1);
						} else {
							$value = '"'.$escape($value).'"';
						}
					}
				}
				if (!empty($output)){
					$output .= ",\n";
				}
				$output .= "{$stIndent}\t\"{$key}\": {$value}";
			}
			$output = ($indentFirstLine ? $indent : '') . "{\n{$output}\n{$stIndent}}";
		} else {
			$output = empty($src) ? '' : "'" . $escape($src) . "'";
			$output = $stIndent . '{' . $output . '}';
		}
		return $output;
	}

	/** 
	 * @brief Get/Set the current language.
	 * Gets or sets the current language for sending emails if mbstring is installed. Otherwise just here for compatiblity, always returns neutral and only allows setting neutral.
	 * 
	 * @param $language Used for encoding e-mail messages.
	 * 
	 * @return If language is set and language is valid, it returns true. Otherwise, it returns false. When language is omitted or null, it returns the language name as a string.
	 */
	public static function language(?string $language = null):string|bool {
		if (static::$has_mb){
			return mb_language($language);
		} else {
			if (null == $language){
				return 'neutral';
			} else {
				return static::tolower($language) == 'neutral';
			}
		}
	}
	
	/** 
	 * Make a string's first character lowercase.
	 * 
	 * @param $string The string to uppercase.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return The uppercased string.
	 */
	public static function lcfirst(string $string, ?string $encoding=null): string {
		if (static::$has_mb){
			if (function_exists('mb_lcfirst')){
				return mb_lcfirst($string, $encoding);
			} else {
				$output = static::tolower(static::substr($string, 0, 1));
				$output .= static::substr($string, 1);
				return $output;
			}
		} else {
			return lcfirst($string);
		}
	}	
	
	/** 
	 * How many characters are in the passed string?
	 * 
	 * @param $in The string to be counted. 
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return integer
	 */
	public static function len(string $in, ?string $encoding=null):int {
		if (static::$has_mb){
			return mb_strlen($in, $encoding);
		} else {
			return strlen($in);
		}
	}

	/** 
	 * Returns an array of all supported encodings.
	 * 
	 * 
	 * @return Returns a numerically indexed array.
	 */
	public static function list_encodings():array {
		if (static::$has_mb){
			return mb_list_encodings();
		} else {
			return array('ISO-8859-1');
		}
	}
	
	/** 
	 *  @brief Strip whitespace from the beginning of a string.
	 * 
	 * Strip whitespace (or other characters) from the beginning of a string.
	 * Adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param $haystack The string to be stripped. 
	 * @param $needles - String containing a list of characters to be stripped. With .. it is possible to specify an incrementing range of characters.
	 * If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return The trimmed result string.
	 */
	public static function ltrim(string $haystack, string $needles=null, ?string $encoding=null):string {
		if (null == $needles) {
			$needles = static::MB_SPACE_NEEDLE;
		}
		if (empty($haystack)) return $haystack;
		if (static::$has_mb){
			if (function_exists('mb_ltrim')){ //new as of php 8.4
				return mb_ltrim($haystack, $needles, $encoding);
			} else {
				$needles = preg_quote($needles, '/');
				return preg_replace("/(^[$needles]+)/u", '', $haystack);
			}
		} else {
			return ltrim($haystack, $needles);
		}
	}

	/** 
	 * Get code point of a character.
	/ * 
	 * @param $string The string whose first character's code point should be returned.
	 * @param $encoding Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return 
	 */
	public static function ord(string $string, ?string $encoding=null):int|false {
		if (static::$has_mb){
			return mb_ord($string, $encoding);
		} else {
			return ord($string);
		}
	}

	/** 
	 * Pad a multibyte string to a certain length with another multibyte string.
	 * 
	 * @param $string The input string.
	 * @param $length If the value of length is negative, less than, or equal to the length of the input string, no padding takes place, and string will be returned.
	 * @parm pad_string The string to use as padding.
	 * @note The pad_string may be truncated if the required number of padding characters can't be evenly divided by the pad_string's length.
	 * @param $pad_type Optional argument pad_type can be STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH. By default STR_PAD_RIGHT.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return The padded string. 
	 */
	public static function pad(string $string, int $length, string $pad_string=' ', int $pad_type=STR_PAD_RIGHT, ?string $encoding=null):string {
		if (static::$has_mb){
			if (function_exists('mb_str_pad')){
				return mb_str_pad($string, $length, $pad_string, $pad_type, $encoding);
			} else {
				$padleft = true;
				$padright = true;
				switch ($pad_type){
					case STR_PAD_RIGHT:
						$padleft = false;
						break;
					case STR_PAD_LEFT:
						$padright = false;
						break;
					case STR_PAD_BOTH:
						break;
					default:
						throw new ValueError('str_pad(): Argument #4 ($pad_type) must be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH ');
						break;
				}
				$output = $string;
				$output_len = static::len($output, $encoding);
				if ($output_len < $length){
					$pad_index = 0;
					$pad_len = static::len($pad_string, $encoding);
					while ($output_len < $length){
						$pad_char = static::substr($pad_string, $pad_index, 1, $encoding);
						$pad_index++;
						$pad_index = $pad_index>=$pad_len ? 0 : $pad_index;
						if ($padright){
							$output .= $pad_char;
							$output_len++;
						}
						if ($padleft && $output_len < $length){
							$output = $pad_char . $output;
							$output_len++;
						}
					}
				}
				return $output;
			}
		} else {
			return str_pad($string, $length, $pad_string, $pad_type);
		}
	}
	
	/** 
	 * Parse GET/POST/COOKIE data and set global variable.
	 * 
	 * @param $string The urlencoded data.  
	 * @param $result An array containing decoded and character encoded converted values.
	 * @param $encoding Currently not used, present for consistency
	 *
	 * @return With multibyte - true/false success/failure or without multibyte always true.
	 */
	public static function parse(string $string, array &$result, ?string $encoding=null):bool {
		if (static::$has_mb){
			 return mb_parse_str($string, $result);
		 } else {
			 parse_str($string, $result);
			 return true;
		 }
	}
	
	/** 
	 * Find the position of the first occurrence of a substring in a string
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - If specified, search will start this number of characters counted from the beginning of the string. If the offset is negative, the search will start this number of characters counted from the end of the string.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Position number or false if not found as an int.
	 */
	public static function pos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = false;
		if (static::$has_mb){
			$output =  mb_strpos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strpos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * Repeat a string.
	 * 
	 * @param $string The string to be repeated.
	 * @param $times  How many times to repeat it.
	 * @param $encoding Unncessary for this method, only included to match other UStr methods.
	 *
	 * @return A string consisting of $string repeated $times times.
	 */
	public static function repeat(string $string, int $times, ?string $encoding=null):string {
		return str_repeat($string, $times);
	}
	
	/** 
	 * Replace all occurrences of the search string with the replacement string
	 * 
	 * @param $search - The string segment that is to be replaced.
	 * @param $replace - The segment that is to replace the search segment.
	 * @param $subject - The input string.
	 * @param &$count - If passed, this will be set to the number of replacements performed.
	 * @param $encoding Currently not used, present for consistency
	 *
	 * @return string
	 */
	public static function replace(array|string $search, array|string $replace, string|array $subject, int &$count=null, ?string $encoding=null):string|array {
		return str_replace($search, $replace, $subject, $count);
	}

	/** 
	 * Finds position of last occurrence of a string within another, case insensitive.
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - Where to begin the search.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Position as an int or false if not found.
	 */
	public static function ripos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = null;
		if (static::$has_mb){
			$output =  mb_strripos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strripos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * Rotates each character of a string by $distance characters within it's block.
	 * @note If the block contains an odd number of characters do not rotate the greatest character. This way rotations which cancel themselves out (like rot13 w/ Latin) still work.
	 * 
	 * @param distance If integer, how many places to shift the character. If it takes the character past the end of the block wrap around. Do so multiple times if necessary. If a float between 0 and 1 then multiply this by the number of characters in the block (skipping the last one if it is odd). Round to nearest. Rotate that many places. Reason for this is 0.5 then has the same self-canceling effect for all blocks as 13 does for the Latin alphabet.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @warning Substitution cyphers should not be relied on for any form of security. Rotation cyphers in particular should not be used for anything important.
	 * @return 
	 */
	public static function rot(string $string, int|float $distance=null, ?string $encoding=null):string {
		if (0 == Static::len($string, $encoding)){
			return $string;
		}
		if (0 < $distance-floor($distance)){ //for franctional distances lets get rid of any whole part as that would just wrap around anyway
			$distance = $distance-floor($distance);
		}
		$output = '';
		$block = null;
		$end = Static::len($string);
		$cur_distance = 0;
		$block_len = 0;
		$end_cap = 0;
		for ($i=0; $i<$end; $i++){
			$char = Static::substr($string, $i, 1, $encoding);
			$char = UStr::ord($char);
			if (null==$block || $char<$block->start || $char>$block->end){
				$block = UCharBlock::FindAlpha($char);
				if (null != $block){
					$block_len = $block->count;
					$end_cap =  0;
					if (1 == $block_len % 2) {
						$end_cap = $block->end;
						$block_len--;
					}
					if (0 == $distance - floor($distance)){
						$cur_distance = $distance;
					} else {
						$cur_distance = round($block_len * $distance);
					}
				}
			}
			if (null == $block || $char == $end_cap){
				$output .= UStr::chr($char);
			} else {
				$char += $cur_distance;
				if ($char >= ($block->start + $block_len)){
					$char -= $block_len;
				}
				$output .= UStr::chr($char);
			}
		}
		return $output;
	}
	
	/** 
	 * Find the position of the last occurrence of a substring in a string
	 * 
	 * @param $haystack - The string to be searched.
	 * @param $needle - The string to search for.
	 * @param $offset - Where to begin the search.
	 * - If zero or positive, the search is performed left to right skipping the first offset bytes of the haystack.
	 * - If negative, the search starts offset bytes from the right instead of from the beginning of haystack. The search is performed right to left, searching for the first occurrence of needle from the selected byte.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Position as an int or false if not found.
	 */
	public static function rpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null):int|false {
		$output = null;
		if (static::$has_mb){
			$output =  mb_strrpos($haystack, $needle, $offset, $encoding);
		} else {
			$output = strrpos($haystack, $needle, $offset);
		}
		return $output;
	}

	/** 
	 * @preview Strip whitespace from the end of a string.
	 * 
	 * Strip whitespace (or other characters) from the end of a string.
	 * Adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param $haystack The string to be stripped.
	 * @param $needles List of characters to be stripped. With .. it is possible to specify an incrementing range of characters. If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.	 * @return string the trimmed result. 
	 */
	public static function rtrim(string $haystack, string $needles=null, ?string $encoding=null):string {
		if (null == $needles) {
			$needles = static::MB_SPACE_NEEDLE;
		}
		if (empty($haystack)) return $haystack;
		if (static::$has_mb){
			if (function_exists('mb_trim')){ //new as of php 8.4
				return mb_trim($haystack, $needles);
			} else {
				$needles = preg_quote($needles, '/');
				return preg_replace("/([$needles]+$)/u", '', $haystack);
			}
		} else {
			return rtrim($haystack, $needles);
		}
	}

	/** 
	 * Randomly shuffles a string.
	 * 
	 * @param $string The string to be shuffled.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return A new string made up of the input string's characters shuffled into a random order.
	 */
	public static function shuffle(string $string, ?string $encoding=null):string {
		if (static::$has_mb){
			$output = '';
			$arString = static::split($string, 1, $encoding);
			while (0 < $chars = count($arString)){
				$i = 1 == $chars ? 0 : rand(0, $chars-1);
				$output .= $arString[$i];
				array_splice($arString, $i, 1);
			}
			return $output;
		} else {
			return str_shuffle($string);
		}
	}
	
	/** 
	 * Split a string into an array by character count.
	 * 
	 * @param $string String to be split.
	 * @param $length Charachter length of split sections.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return array of strings
	 */
	public static function split(string $string, int $length=1, ?string $encoding=null): array {
		if (static::$has_mb){
			return mb_str_split($string, $length, $encoding);
		} else {
			return str_split($string, $length);
		}
	}

	/** 
	 * Finds the length of the initial segment of a string consisting entirely of characters contained within a given mask.
	 * 
	 * @param $string The string to examine. 
	 * @param $characters The list of allowable characters.
	 * @param $offset The position in string to start searching.
	 * - If offset is given and is non-negative, then strspn() will begin examining string at the offset'th position. For instance, in the string 'abcdef', the character at position 0 is 'a', the character at position 2 is 'c', and so forth.
	 * - If offset is given and is negative, then strspn() will begin examining string at the offset'th position from the end of string.
	 * @param $length The length of the segment from string to examine.
	 * - If length is given and is non-negative, then string will be examined for length characters after the starting position.
	 * - If length is given and is negative, then string will be examined from the starting position up to length characters from the end of string.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns the length of the initial segment of string which consists entirely of characters in characters. 
	 */
	public static function spn(string $string, string $characters, int $offset=0, ?int $length=null, ?string $encoding=null):int {
		if (static::$has_mb){
			if (!(is_null($length)&&0==$offset)){
				$string = static::substr($string, $offset, $length, $encoding);
			}
			$characters = static::toCharList($characters, $encoding);
			$len = static::len($string, $encoding);
			for ($i=0; $i<$len; $i++){
				$output = $i;
				$char = static::substr($string, $i, 1, $encoding);
				if(!in_array($char, $characters)){
					break;
				}
			}
			return $i;
		} else {
			return strspn($string, $characters, $offset, $length);
		}
	}
	
	/** 
	 * Does the haystack string begin with the contents of the needle string?
	 * 
	 * @param $haystack String to be searched.
	 * @param $needle String or character to search for.
	 * @param $caseSensitive False to ignore case.
	 * @param $encoding Currently not used, present for consistency.
	 *
	 * @return bool
	 */
	public static function starts_with(string $haystack, string $needle, bool $caseSensitive=true, ?string $encoding=null):bool {
		if (!$caseSensitive){
			return static::starts_with(static::tolower($haystack), static::tolower($needle));
		}
		if (function_exists('str_starts_with')){
			return str_starts_with($haystack, $needle);
		} else {
			$len_needle = static::len($needle);
			if (0 == $len_needle){
				return true;
			} else {
				if (static::len($haystack) < $len_needle) {
					return false;
				} else {
					return static::substr($haystack, 0, $len_needle) == $needle;
				}	
			}
		}
	}

	/** 
	 * @brief Finds first occurrence of a string within another.
	 * Finds the first occurrence of needle in haystack and returns the portion of haystack. If needle is not found, it returns false.
	 * 
	 * @param $haystack The string from which to get the first occurrence of needle.
	 * @param $needle The string to find in haystack.
	 * @param $before_needle Determines which portion of haystack this function returns.
	 * - If set to true, it returns all of haystack from the beginning to the first occurrence of needle (excluding needle).
	 * - If set to false, it returns all of haystack from the first occurrence of needle to the end (including needle).
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns the portion of haystack, or false if needle is not found.
	 */
	public static function str(string $haystack, string $needle, bool $before_needle = false, ?string $encoding = null):string|false {
		if (static::$has_mb){
			return mb_strstr($haystack, $needle, $before_needle, $encoding);
		} else {
			return strstr($haystack, $needle, $before_needle);
		}
	}
	
	/** 
	 * Strip HTML and PHP tags from a string.
	 *
	 * @param $string The string.
	 * @param $allowed_tags You can use the optional second parameter to specify tags which should not be stripped. These are either given as string, or as of PHP 7.4.0, as array. Refer to the example below regarding the format of this parameter.
	 * @param $encoding Does not currently do anything, present only for consistency.
	 * 
	 * @return 
	 */
	public static function strip_tags(string $string, array|string|null $allowed_tags=null, ?string $encoding=null):string {
		return strip_tags($string, $allowed_tags);
	}

	/** 
	 * Un-quote string quoted with addcslashes().
	 * 
	 * @param $string The string to be unescaped.
	 * @param $encoding Not currently in use, here for consistency.
	 * 
	 * @return 
	 */
	public static function stripcslashes(string $string, ?string $encoding = null){
		$output = $string;
		if (static::$has_mb)
		{
			while(mb_ereg("\\\U[0-9a-fA-F]{8}|\\\u[0-9a-fA-F]{4}", $output, $match)){
				$replacement = static::chr(hexdec(substr($match[0], 2)));
				$output = static::replace($match[0], $replacement, $output);
			}
		}
		$output = stripcslashes($output);
		return $output;
	}
	
	/** 
	 * @brief Set/Get substitution character.
	 * Specifies a substitution character when input character encoding is invalid or character code does not exist in output character encoding. Invalid characters may be substituted "none" (no output), string or int value (Unicode character code value).
	 *
	 * @param substitute_character Specify the Unicode value as an int, or as one of the following strings:
	 * - "none": no output
	 * - "long": Output character code value (Example: U+3000, JIS+7E7E)
	 * - "entity": Output character entity (Example: &#x200;)
	 * 
	 * @return If substitute_character is set, it returns true for success, otherwise returns false. If substitute_character is not set, it returns the current setting. 
	 */
	public static function substitute_character(string|int|null $substitute_character=null):string|int|bool {
		if (static::$has_mb){
			return mb_substitute_character($substitute_character);
		} else {
			switch ($substitute_character){
				case null:
					return 63;
					break;
				case 63:
					return true;
					break;
				default:
					return false;
					break;
			}
		}
	}
	
	/** 
	 * Return part of a string
	 * 
	 * @param $in - String to take a substring of.
	 * @param $offset - Where to start the substring.
	 * - If offset is non-negative, the returned string will start at the offset'th position in string, counting from zero. For instance, in the string 'abcdef', the character at position 0 is 'a', the character at position 2 is 'c', and so forth.
	 * - If offset is negative, the returned string will start at the offset'th character from the end of string.
	 * - If string is less than offset characters long, an empty string will be returned.
	 * @param $length - Length of substring.
	 * - If length is given and is positive, the string returned will contain at most length characters beginning from offset (depending on the length of string).
	 * - If length is given and is negative, then that many characters will be omitted from the end of string (after the start position has been calculated when a offset is negative). If offset denotes the position of this truncation or beyond, an empty string will be returned.
	 * - If length is given and is 0, an empty string will be returned.
	 * - If length is omitted or null, the substring starting from offset until the end of the string will be returned
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return string
	 */
	public static function substr(string $in, int $offset, ?int $length=null, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_substr($in, $offset, $length, $encoding);
		} else {
			return substr($in, $offset, $length);
		}
	}

	/** 
	 * @brief Binary safe comparison of two strings from an offset, up to length characters.
	 * Compares haystack from position offset with needle up to length characters.
	 * 
	 * @param $haystack The main string being compared. 
	 * @param $needle The secondary string being compared.
	 * @param $offset The start position for the comparison. If negative, it starts counting from the end of the string.
	 * @param $length The length of the comparison. The default value is the largest of the length of the needle compared to the length of haystack minus the offset.
	 * @param $case_insensitive If case_sensitive is true comparison is case insensitive.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns a value less than 0 if string1 is less than string2; a value greater than 0 if string1 is greater than string2, and 0 if they are equal. No particular meaning can be reliably inferred from the value aside from its sign.
	 */
	public static function substr_compare(
		string $haystack,
		string $needle,
		int $offset,
		?int $length = null,
		bool $case_insensitive = false,
		?string $encoding = null
	): int {
		if (static::$has_mb){
			$hay_len = static::len($haystack, $encoding);
			$ned_len = static::len($needle, $encoding);
			$start = 0 > $offset ? ($offset + $hay_len) : $offset;
			$start = min($hay_len-1, $start);
			$start = max($start, 0);
			if (is_null($length)){
				$length = $hay_len - $offset;
				$length = max($end, $ned_len);
			}
			if (0 > $length){
				throw new ValueError('UStr::substr_count(): Argument #4 ($length) must be greater than or equal to 0');
			}
			$target = static::substr($haystack, $start, $length, $encoding);
			$ned = static::substr($needle, 0, $length, $encoding);
			return static::cmp($target, $ned, $encoding);
		} else {
			return substr_compare($haystack, $needle,  $offset, $length, $case_insensitive);
		}
	}
	
	/** 
	 * Count the number of substring occurences.
	 * 
	 * @param $haystack The string to search in.
	 * @param $needle The substring to search for.
	 * @param $offset The offset where to start counting. If the offset is negative, counting starts from the end of the string. 
	 * @param $length The maximum length after the specified offset to search for the substring. It outputs a warning if the offset plus the length is greater than the haystack length. A negative length counts from the end of haystack.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return This function returns an int, the number of times the needle substring occurs in the haystack string.
	 */
	public static function substr_count(string $haystack, string $needle, int $offset=0, ?int $length=null, ?string $encoding=null):int {
		if (static::$has_mb) {
			if (!(0==$offset && is_null($length))){
				if (static::len($haystack, $encoding) < ($offset+(is_null($length)?0:$length))){
					throw new ValueError('UStr::substr_count(): Argument #4 ($length) must be contained in argument #1 ($haystack)');
				}
				$haystack = static::substr($haystack, $offset, $length, $encoding);
			}
			return mb_substr_count($haystack, $needle, $encoding);
		} else {
			return substr_count($haystack, $needle, $offset, $length);
		}
	}

	/** 
	 * Replace text within a portion of a string.
	 * 
	 * @param $string The input string. An array of strings can be provided, in which case the replacements will occur on each string in turn. In this case, the replace, offset and length parameters may be provided either as scalar values to be applied to each input string in turn, or as arrays, in which case the corresponding array element will be used for each input string.
	 * @param $replace The replacement string. 
	 * @param $offset If offset is non-negative, the replacing will begin at the offset'th offset into string. If offset is negative, the replacing will begin at the offset'th character from the end of string.
	 * @param $length If given and is positive, it represents the length of the portion of string which is to be replaced. If it is negative, it represents the number of characters from the end of string at which to stop replacing. If it is not given, then it will default to strlen( string ); i.e. end the replacing at the end of string. Of course, if length is zero then this function will have the effect of inserting replace into string at the given offset offset.
	 * 
	 * @return The result string is returned. If string is an array then array is returned. 
	 */
	public static function substr_replace(
		array|string $string,
		array|string $replace,
		array|int $offset,
		array|int|null $length=null,
		?string $encoding=null
	):string|array {
		if (static::$has_mb){
			if (is_array($string)){
				$output = array();
				for ($i = 0; $i < count($string); $i++){
					$i_string = $string[$i];
					$i_replace = $replace;
					if (is_array($replace)){
						$i_replace = $i < count($replace) ? $replace[$i] : null;
					}
					$i_offset = $offset;
					if (is_array($offset)){
						$i_offset = $i < count($offset) ? $offset[$i] : null;
					}
					$i_length = $length;
					if (is_array($length)){
						$i_length = $i < count($length) ? $length[$i] : null;
					}
					$output[] = static::substr_replace($string, $replace, $offset, $length, $encoding);
				}
				return $output;
			} else {
				$replace = is_array($replace) ? $replace[0] : $replace;
				$offset = is_array($offset) ? $offset[0] : $offset;
				$length = is_array($length) ? $length[0] : $length;
				$len_string = static::len($string, $encoding);
				$offset = 0 > $offset ? ($len_string + $offset) : $offset;
				$offset = max(0, $offset);
				if (0 > $length){
					$end = $length + $len_string;
					$end = max($end, $offset);
				} else {
					$end = $offset + $length;
				}
				$output = '';
				if (!is_null($offset)){
					$output .= static::substr($string, 0, $offset);
				}
				$output .= $replace;
				if (!is_null($length)){
					$output .= static::substr($string, $end);
				}
				return $output;
			}
		} else {
			return substr_replace($string, $replace, $offset, $length);
		}
	}
	
	/** 
	 * @brief Convert string to list of characters.
	 * Converts a string to an array of characters.  Intereprets ".." between characters as a range. Converts escape sequences to their characters.
	 *
	 * @param $string The string.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
 	 * 
	 * @return An array of characters.
	 */
	public static function toCharList(string $string, ?string $encoding = null):array {
		$output = array();
		$string = static::stripcslashes($string, $encoding);
		$length = static::len($string, $encoding);
		for ($i = 0; $i < $length; $i++){
			$char = static::substr($string, $i, 1, $encoding);
			if ($i < ($length-4) && '..' == static::substr($string, $i+1, 2, $encoding)){
				$endChar = static::substr($string, $i+3, 1, $encoding);
				$i += 3;
				$ordStart = static::ord($char, $encoding);
				$ordEnd = static::ord($endChar, $encoding);
				if ($ordStart > $ordEnd){
					$bucket = $ordEnd;
					$ordEnd = $ordStart;
					$ordStart = $bucket;
				}
				for ($ord = $ordStart; $ord <= $ordEnd; $ord++){
					$output[] = static::chr($ord, $encoding);
				}
			} else {
				$output[] = $char;
			}
		}
		$output = array_unique($output);
		return $output;
	}
	
	/** 
	 * Generate a lowercase version of the string
	 * 
	 * @param $in - Input string to be converted.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return Lowercased output string.
	 */
	public static function tolower(string $in, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_strToLower($in, $encoding);
		} else {
			return strToLower($in);
		}
	}

	/** 
	 * Generate an uppercase version of the string
	 * 
	 * @param $in Input string.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Uppercased output string.
	 */
	public static function toupper(string $in, ?string $encoding=null):string {
		if (static::$has_mb){
			return mb_strToUpper($in, $encoding);
		} else {
			return strToUpper($in);
		}
	}

	/** 
	 * @brief Tokenize string.
	 * Splits a string (string) into smaller strings (tokens), with each token being delimited by any character from token. That is, if you have a string like "This is an example string" you could tokenize this string into its individual words by using the space character as the token.
	 *
	 * @note Only the first call to strtok uses the string argument. Every subsequent call to strtok only needs the token to use, as it keeps track of where it is in the current string. To start over, or to tokenize a new string you simply call strtok with the string argument again to initialize it. Note that you may put multiple tokens in the token parameter. The string will be tokenized when any one of the characters in the token argument is found.
	 * 
	 * @param $string The string being split up into smaller strings (tokens).
	 * @param $token The delimiter used when splitting up string. 
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.	 
	 * 
	 * @return A string token, or false if no more tokens are available.
	 */
	public static function tok(string $token, ?string $string=null, ?string $encoding=null):string|false{
		if (static::$has_mb){
			if (empty($token)){
				return false;
			}
			if (is_null($string)){
				$string = static::$tokString;
			} else {
				static::$tokString = $string;
			}
			if (empty($string)){
				return false;
			}
			$ar_token = static::toCharlist($token, $encoding);
			$len = static::len($string, $encoding);
			$bucket = '';
			for ($i=0; $i<$len; $i++){
				$char = static::substr($string, $i, 1, $encoding);
				if (in_array($char, $ar_token)){
					if (!empty($bucket)){
						static::$tokString = static::substr($string, $i, $len, $encoding);
						return $bucket;
					}
				} else {
					$bucket .= $char;
				}
			}
			static::$tokString = null;
			return empty($bucket) ? false : $bucket;
		} else {
			if (is_null($string)){
				return strtok($token, $string);
			} else {
				return strtok($string, $token);
			}
		}
	}
	private static ?string $tokString = null;
	
	/** 
	 * @brief Translate characters or replace substrings.
	 * - If given three arguments ($encoding not included), this function returns a copy of string where all occurrences of each character in from have been translated to the corresponding character in to, i.e., every occurrence of $from[$n] has been replaced with $to[$n], where $n is a valid offset in both arguments.
	 * If they have different lengths, the extra characters in the longer of the two are ignored. The length of string will be the same as the return value's.
	 * - If given two arguments ($encoding not included), the second should be an array in the form array('from' => 'to', ...). The return value is a string where all the occurrences of the array keys have been replaced by the corresponding values. The longest keys will be tried first. Once a substring has been replaced, its new value will not be searched again.
	 * The keys and the values may have any length, provided that there is no empty key; additionally, the length of the return value may differ from that of string. However, this function will be the most efficient when all the keys have the same size.
	 *
	 * @param $string The string being translated.
	 * @param $from The string being translated to or an array in which case it's in the form array('from' => 'to', ...).
	 * @param $to The string replacing from or null if $from is an array.
	 * @param $encoding Used only if multibyte support is installed. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns the translated string.
	 */
	public static function tr(string $string, string|array $from, ?string $to=null, ?string $encoding=null):string {
		if (is_array($from)){
			return (strtr($string, $from));
		}		
		if (static::$has_mb){
			if (is_null($to)){
				throw new TypeError('Argument #3, $to must be of type string, null given');
			} else {
				$key_len = min(static::len($from, $encoding), static::len($to, $encoding));
				$replace_pairs = array();
				for ($i=0; $i<$key_len; $i++){
					$char_from = static::substr($from, $i, 1, $encoding);
					$char_to = static::substr($to, $i, 1, $encoding);
					$replace_pairs[$char_from] = $char_to;
				}
				return(strtr($string, $replace_pairs));
			}
		} else {
			return strtr($string, $from, $to);
		}
	}

	/** 
	 *  @brief Strip whitespace from the beginning and end of a string.
	 *
	 * Strip whitespace (or other characters) from the beginning and end of a string.
	 * Solution adapted from https://stackoverflow.com/questions/10066647/multibyte-trim-in-php
	 * @param $haystack String to be trimmed. 
	 * @param $needles - List of characters to be stripped. With .. it is possible to specify an incrementing range of characters. If not specified then the following characters will be stripped.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding Used only if multibyte support is installed and PHP >= 8.4. The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return The trimmed result string.
	 */
	public static function trim(string $haystack, string $needles=null, ?string $encoding=null):string {
		if (null == $needles) {
			$needles = static::MB_SPACE_NEEDLE;
		}
		if (empty($haystack)) return $haystack;
		if (static::$has_mb){
			if (function_exists('mb_trim')){ //new as of php 8.4
				return mb_trim($haystack, $needles);
			} else {
				$needles = preg_quote($needles, '/');
				return preg_replace("/(^[$needles]+)|([$needles]+$)/u", '', $haystack);
			}
		} else {
			return trim($haystack, $needles);
		}
	}

	/** 
	 * Make a string's first character uppercase.
	 * 
	 * @param $string The string to uppercase.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return The uppercased string.
	 */
	public static function ucfirst(string $string, ?string $encoding=null): string {
		if (static::$has_mb){
			if (function_exists('mb_ucfirst')){
				return mb_ucfirst($string, $encoding);
			} else {
				return static::toupper(static::substr($string, 0, 1)).static::substr($string, 1);
			}
		} else {
			return ucfirst($string);
		}
	}
	
	/** 
	 * Make the first character of each word in a string uppercase.
	 * 
	 * @param $string The string to uppercase.
	 * @param $separators - List of characters to be considered whitespace that may separate words.
	 * - " ": ASCII SP character 0x20, an ordinary space.
	 * - "\t": ASCII HT character 0x09, a tab.
	 * - "\n": ASCII LF character 0x0A, a new line (line feed).
	 * - "\r": ASCII CR character 0x0D, a carriage return.
	 * - "\0": ASCII NUL character 0x00, the NUL-byte.
	 * - "\v": ASCII VT character 0x0B, a vertical tab.
	 * - "\f": Form Feed
	 * - "\x00": Null character
	 * - "\u00A0": NO-BREAK SPACE.
	 * - "\u1680": OGHAM SPACE MARK.
	 * - "\u2000": EN QUAD.
	 * - "\u2001": EM QUAD.
	 * - "\u2002": EN SPACE.
	 * - "\u2003": EM SPACE.
	 * - "\u2004": THREE-PER-EM SPACE.
	 * - "\u2005": FOUR-PER-EM SPACE.
	 * - "\u2006": SIX-PER-EM SPACE.
	 * - "\u2007": FIGURE SPACE.
	 * - "\u2008": PUNCTUATION SPACE.
	 * - "\u2009": THIN SPACE.
	 * - "\u200A": HAIR SPACE.
	 * - "\u2028": LINE SEPARATOR.
	 * - "\u2029": PARAGRAPH SEPARATOR.
	 * - "\u202F": NARROW NO-BREAK SPACE.
	 * - "\u205F": MEDIUM MATHEMATICAL SPACE.
	 * - "\u3000": IDEOGRAPHIC SPACE.
	 * - "\u0085": NEXT LINE (NEL).
	 * - "\u180E": MONGOLIAN VOWEL SEPARATOR.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 *
	 * @return The uppercased string.
	 */
	public static function ucwords(string $string, ?string $separators=null, ?string $encoding=null): string {
		if (null == $separators) {
			$separators = static::MB_SPACE_NEEDLE;
		}
		if (static::$has_mb){
			$output = '';
			for ($i = 0; $i < mb_strlen($string); $i++){
				$prev = 0 == $i ? ' ' : mb_substr($string, $i-1, 1, $encoding);
				$cur = mb_substr($string, $i, 1, $encoding);
				$output .= false === mb_strpos($separators, $prev, 0, $encoding) ? $cur : mb_strtoupper($cur, $encoding);
			}
			return $output;
		} else {
			return ucwords($string);
		}
	}	

	/** 
	 * @brief Return the width of a string.
	 * Returns sum of character widths in a string where halfwidth characters count as 1, and fullwidth characters count as 2.
	 *
	 * @note Without mbstring all characters are treated as halfwidth.
	 *
	 * @param $string The string being measured.
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns the width of string string.
	 */
	public static function width(string $string, ?string $encoding = null):int {
		if (static::$has_mb){
			return mb_strwidth($string, $encoding);
		} else {
			return strlen($string);
		}
	}

	/** 
	 * @brief Return information about words used in a string.
	 * Counts the number of words inside string. If the optional format is not specified, then the return value will be an integer representing the number of words found. In the event the format is specified, the return value will be an array, content of which is dependent on the format. The possible value for the format and the resultant outputs are listed below.
	 *
	 * @note For the purpose of this function, 'word' is defined as a string containing alphabetic characters, which also may contain, but not start with "'" and "-" characters. 
	 *
	 * @warning This function may still require some more tweaking regarding which letters are and are not part of a word for multibyte strings.
	 *
	 * @param $string The string.
	 * @param $format Specify the return value of this function. The current supported values are:
	 * - 0 - returns the number of words found
	 * - 1 - returns an array containing all the words found inside the string
	 * - 2 - returns an associative array, where the key is the numeric position of the word inside the string and the value is the actual word itself
	 * @param $characters A list of characters that would otherwise split a word but should not. 
	 * @param $encoding The encoding parameter is the character encoding. If it is omitted or null, the internal character encoding value will be used.
	 * 
	 * @return Returns an array or an integer, depending on the format chosen.
	 */
	public static function word_count(string $string, int $format=0, ?string $characters=null, ?string $encoding=null):array|int {
		if (static::$has_mb){
			$ar_characters = empty($characters) ? array() : static::toCharList($characters, $encoding);
			$ar_space = static::toCharList(static::MB_WORD_SEPARATOR, $encoding);
			$len = static::len($string, $encoding);
			switch ($format) {
				case 0: //returns the number of words found
					$count = 0;
					$counted = false;
					for ($i=0; $i<$len; $i++){
						$char = static::substr($string, $i, 1, $encoding);
						if (in_array($char, $ar_space) && !in_array($char, $ar_characters)){
							$counted = false;
						} else if(!$counted){
							$count++;
							$counted = true;
						}
					}
					return $count;
					break;
				case 1: //returns an array containing all the words
				case 2: //returns an associative array, value is word, key is position in string
					$output = array();
					$word = '';
					$pos = -1;
					for ($i=0; $i<$len; $i++){
						$char = static::substr($string, $i, 1, $encoding);
						if (in_array($char, $ar_space) && !in_array($char, $ar_characters)){
							if (!empty($word)){
								if (2 == $format){
									$output[$pos] = $word;
								} else {
									$output[] = $word;
								}
								$word = '';
								$pos = -1;
							}
						} else {
							$pos = $i;
							$word .= $char;
						}
					}
					if (!empty($word)){
						if (2 == $format){
							$output[$pos] = $word;
						} else {
							$output[] = $word;
						}
					}
					return $output;
					break;
				default:
					throw new ValueError('Argument #2 ($format) must be a valid format value');
					break;
			}
			return 0;
		} else {
			return str_word_count($string, $format, $characters);
		}
	}
}
?>
