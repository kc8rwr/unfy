<?php

/** 
 * Class to represent a block of unicode characters.
 * 
 * @param type alpha/ideograph/number.
 * @param name The name of the block.
 * @param is_capital True for upper-case, false for lower-case or null for un-cased.
 * @param start The starting unicode number.
 * @param end The ending unicode number.
 * @param notes Any additional notes about this block.
 * 
 */
class UCharBlock {
	public readonly string $type;
	public readonly string $name;
	public readonly bool $isCapital;
	public readonly int $start;
	public readonly int $end;
	public readonly string $notes;
	public readonly int $count;
	
	public function __construct(string $type, string $name, ?bool $is_capital, int $start, int $end, string $notes = ''){
		$this->type = $type;
		$this->name = $name;
		$this->isCapital = $is_capital;
		$this->start = $start;
		$this->end = $end;
		$this->notes = $notes;
		$this->count = $this->end - $this->start + 1;
	}

	/** 
	 * Given a character or unicode number find the UCharBlock it belongs to.
	 * 
	 * @param character The character, first character of a string or the unicode number as an int.
	 * 
	 * @return The UCharBlock or null if this does not fall in one of the blocks.
	 */	public static function FindAlpha(int|string $character):UCharBlock|false {
		$output = false;
		if (is_string($character) && 0 < UStr::len($character)){
			$character = UStr::ord($character);
		}
		if (is_int($character))
		{
			if ($character > 0x0040 && $character < 0x005B){ //Latin Upper
				$output = new UCharBlock('alpha', 'Latin Capital', true, 0x0041, 0x005A);
			} else if ($character > 0x0060 && $character < 0x007B){ //Latin Lower
				$output = new UCharBlock('alpha', 'Latin Lower', false, 0x0061, 0x007A);
			} else if ($character < 0x0D00) {
				if ($character < 0x0980){
					if ($character > 0x0390 && $character < 0x03AA) { //Greek Upper
						$output = new UCharBlock('alpha', 'Greek Upper', true, 0x0391, 0x03A9);
					} else if ($character > 0x03B0 && $character < 0x03CA) { //Greek Lower
						$output = new UCharBlock('alpha', 'Greek Lower', false, 0x03B1, 0x03C9);
					} else if ($character > 0x040F && $character < 0x0430) { //Cyrillic Upper
						$output = new UCharBlock('alpha', 'Cyrillic Upper', true, 0x0410, 0x042F);
					} else if ($character > 0x042F && $character < 0x0450) { //Cyrillic Lower
						$output = new UCharBlock('alpha', 'Cyrillic Lower', false, 0x0430, 0x044F);
					} else if ($character > 0x0530 && $character < 0x0557){ //Armenian Upper
						$output = new UCharBlock('alpha', 'Armenian Upper', true, 0x0531, 0x0556);
					} else if ($character > 0x0560 && $character < 0x0587){ //Armenian Lower
						$output = new UCharBlock('alpha', 'Armenian Upper', true, 0x0561, 0x0586);
					} else if ($character > 0x05CF && $character < 0x05EB){ //Hebrew
						$output = new UCharBlock('alpha', 'Hebrew', null, 0x05D0, 0x05EA);
					} else if ($character > 0x0626 && $character < 0x0643){ //Arabic
						$output = new UCharBlock('alpha', 'Arabic', null, 0x0627, 0x0642);
					} else if ($character > 0x070F && $character < 0x0730){ //Syriac
						$output = new UCharBlock('alpha', 'Syriac', null, 0x0710, 0x072F);
					} else if ($character > 0x077F && $character < 0x07B2){ //Thaana
						$output = new UCharBlock('alpha', 'Thaana', null, 0x0780, 0x07B1);
 					} else if ($character > 0x08FF && $character < 0x0980){ //Devanagari
						$output = new UCharBlock('alpha', 'Devanagari', null, 0x0900, 0x097F);
					}
				} else { // $character < 0x0D00 && $character ≥ 0x0980
					if ($character > 0x097F && $character < 0x1000){ //Bengali
						$output = new UCharBlock('alpha', 'Bengali', null, 0x0980, 0x09FF);
					} else if ($character > 0x09FF && $character < 0x0A80){ // Gurmukhi
						$output = new UCharBlock('alpha', 'Gurmukhi', null, 0x0A00, 0x0A7F);
					} else if ($character > 0x0A7F && $character < 0x0B00){ // Gujarati
						$output = new UCharBlock('alpha', 'Gujarati', null, 0x0A80, 0x0AFF);
					} else if ($character > 0x0AFF && $character < 0x0B80){ // Oriya
						$output = new UCharBlock('alpha', 'Oriya', null, 0x0B00, 0x0B7F);
					} else if ($character > 0x0B7F && $character < 0x0C00){ // Tamil
						$output = new UCharBlock('alpha', 'Tamil', null, 0x0B80, 0x0BFF);
					} else if ($character > 0x0BFF && $character < 0x0C80){ // Telugu
						$output = new UCharBlock('alpha', 'Telugu', null, 0x0C00, 0x0C7F);
					} else if ($character > 0x0C7F && $character < 0x0D00){ // Kannada
						$output = new UCharBlock('alpha', 'Kanadda', null, 0x0C80, 0x0CFF);
					} else if ($character > 0x0CFF && $character < 0x0D80){ // Malayalam
						$output = new UCharBlock('alpha', 'Malayalam', null, 0x0D00, 0x0D7F);
					}
				}
			} else { // $character ≥ 0x0D00
				if ($character < 0x1680){
					if ($character > 0x0D7F && $character < 0x0E00){ // Sinhala
						$output = new UCharBlock('alpha', 'Sinhala', null, 0x0D80, 0x0DFF);
					} else if ($character > 0x0DFF && $character < 0E80){ // Thai
						$output = new UCharBlock('alpha', 'Thai', null, 0x0ED0, 0x0E7F);
					} else if ($character > 0x0E7F && $character < 0x0F00){ // Lao
						$output = new UCharBlock('alpha', 'Lao', null, 0x0E80, 0x0EFF);
					} else if ($character > 0x109F && $character < 0x1100){ // Georgian
						$output = new UCharBlock('alpha', 'Georgian', null, 0x10A0, 0x10FF);
					} else if ($character > 0x10FF && $character < 0x1200){ //Hangul Jamo
						$output = new UCharBlock('alpha', 'Hangul Jamo', null, 0x1100, 0x11FF);
					} else if ($character > 0x11FF && $character < 0x1380){ // Ethiopic
						$output = new UCharBlock('alpha', 'Ethiopic', null, 0x1200, 0x137F);
					} else if ($character > 0x139F && $character < 0x1400){ // Cherokee
						$output = new UCharBlock('alpha', 'Cherokee', null, 0x13A0, 0x13FF);
					} else if ($character > 0x13FF && $character < 0x1680){ // Canadian Aboriginal
						$output = new UCharBlock('alpha', 'Canadian Aboriginal', null, 0x1400, 0x167F);
					}
				} else if ($character < 0x309F){ //$character ≥ 0x1680
					if ($character > 0x167F && $character < 0x16A0){ // Ogham - Historic
						$output = new UCharBlock('alpha', 'Ogham', null, 0x1680, 0x169F, 'Historc');
					} else if ($character > 0x169F && $character < 0x1700){ // Runic - Historic
						$output = new UCharBlock('alpha', 'Runic', null, 0x16A0, 0x16FF, 'Historic');
					} else if ($character > 0x177F && $character < 0x1800){ // Khmer
						$output = new UCharBlock('alpha', 'Khmer', null, 0x1780, 0x17FF);
					} else if ($character > 0x17FF && $character < 0x18B0){ // Mongolian
						$output = new UCharBlock('alpha', 'Mongolian', null, 0x1800, 0x18AF);
					} else if ($character > 0x2DFF && $character < 0x2E80){ // Ideographich Description Chars
						$output = new UCharBlock('ideograph', 'Ideographic Description Chars', null, 0x2E00, 0x2E7F);
					} else if ($character > 0x2E7F && $character < 0x2F00){ // Korean (Hanja - Additional)
						$output = new UCharBlock('ideograph', 'Korean (Hanja - Additional)', null, 0x2E80, 0x2EFF);
					} else if ($character < 0x2EFF && $character < 0x2FE0){ // Unified Ideographs
						$output = new UCharBlock('ideograph', 'Unified Ideographs', null, 0x2F00, 0x2FDF);
					} else if ($character > 0x303F && $character < 0x30A0){ //Hiragana
						$output = new UCharBlock('alpha', 'Hiragana', null, 0x3040, 0x309F);
					}
				} else {
					if ($character > 0x309F && $character < 0x3100){ //Katakana
						$output = new UCharBlock('alpha', 'Katakana', null, 0x30A0, 0x30FF);
					} else if ($character > 0x33FF && $character < 0x4DC0){ // Chinese (Ext A)
						$output = new UCharBlock('ideograph', 'Chinese (Ext A)', null, 0x3400, 0x4DBF);
					} else if ($character > 0x4DFF && $character < 0xA000){ // CJK
						$output = new UCharBlock('ideograph', 'CJK', null, 0x4E00, 0x9FFF);
					} else if ($character > 0xABFF && $character < 0xD7B0){ // Hangul Syllables
						$output = new UCharBlock('alpha', 'Hangul Syllables', null, 0xAC00, 0xD7AF, 'Korean precomposed syllables');
					} else if ($character > 0xF8FF && $character < 0xFB00){ //Japanese (Kanji - Additional)
						$output = new UCharBlock('ideograph', 'Japanese (Kanji - Additional)', null, 0xF900, 0xFAFF);
					} else if ($character > 0x1FFFF && $character < 0x2A6E0) {
						$output = new UCharBlock('ideograph', 'Chinese (Ext B)', null, 0x20000, 0x2A6DF);
					} else if ($character > 0x2A6FF && $character < 0x2B740){ // Chinese (Ext C)
						$output = new UCharBlock('ideograph', 'Chinese (Ext C)', null, 0x2A700, 0x2B73F);
					} else if ($character > 0x2B73F && $character < 0x2B820){ // Chinese (Ext D)
						$output = new UCharBlock('ideograph', 'Chinese (Ext D)', null, 0x2B740, 0x2B81F);
					} else if ($character > 0x2B81F && $character < 0x3CEB0){ // Chinese (Ext E)
						$output = new UCharBlock('ideograph', 'Chinese (Ext E)', null, 0x2B820, 0x2CEAF);
					}
				}
			}
		}
		return $output;
	}
}

?>
