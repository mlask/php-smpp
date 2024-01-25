<?php
namespace smpp\helpers;

/**
 * Class capable of encoding GSM 03.38 default alphabet and packing octets into septets as described by GSM 03.38.
 * Based on mapping: http://www.unicode.org/Public/MAPPINGS/ETSI/GSM0338.TXT
 *
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class GsmEncoderHelper
{
	/**
	 * Encode an UTF-8 string into GSM 03.38
	 * Since UTF-8 is largely ASCII compatible, and GSM 03.38 is somewhat compatible, unnecessary conversions are removed.
	 * Specials chars such as € can be encoded by using an escape char \x1B in front of a backwards compatible (similar) char.
	 * UTF-8 chars which doesn't have a GSM 03.38 equivalent is replaced with a question mark.
	 * UTF-8 continuation bytes (\x08-\xBF) are replaced when encountered in their valid places, but
	 * any continuation bytes outside of a valid UTF-8 sequence is not processed.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function utf8_to_gsm0338($string)
	{
		$dict = [
			'@' => "\x00",
			'£' => "\x01",
			'$' => "\x02",
			'¥' => "\x03",
			'è' => "\x04",
			'é' => "\x05",
			'ù' => "\x06",
			'ì' => "\x07",
			'ò' => "\x08",
			'Ç' => "\x09",
			'Ø' => "\x0B",
			'ø' => "\x0C",
			'Å' => "\x0E",
			'å' => "\x0F",
			'Δ' => "\x10",
			'_' => "\x11",
			'Φ' => "\x12",
			'Γ' => "\x13",
			'Λ' => "\x14",
			'Ω' => "\x15",
			'Π' => "\x16",
			'Ψ' => "\x17",
			'Σ' => "\x18",
			'Θ' => "\x19",
			'Ξ' => "\x1A",
			'Æ' => "\x1C",
			'æ' => "\x1D",
			'ß' => "\x1E",
			'É' => "\x1F",
			'А' => "\x04\x10",
			'Б' => "\x04\x11",
			'В' => "\x04\x12",
			'Г' => "\x04\x13",
			'Д' => "\x04\x14",
			'Е' => "\x04\x15",
			'Ё' => "\x04\x01",
			'Ж' => "\x04\x16",
			'З' => "\x04\x17",
			'И' => "\x04\x18",
			'Й' => "\x04\x19",
			'К' => "\x04\x1A",
			'Л' => "\x04\x1B",
			'М' => "\x04\x1C",
			'Н' => "\x04\x1D",
			'О' => "\x04\x1E",
			'П' => "\x04\x1F",
			'Р' => "\x04\x20",
			'С' => "\x04\x21",
			'Т' => "\x04\x22",
			'У' => "\x04\x23",
			'Ф' => "\x04\x24",
			'Х' => "\x04\x25",
			'Ц' => "\x04\x26",
			'Ч' => "\x04\x27",
			'Ш' => "\x04\x28",
			'Щ' => "\x04\x29",
			'Ь' => "\x04\x2A",
			'Ы' => "\x04\x2B",
			'Ъ' => "\x04\x2C",
			'Э' => "\x04\x2D",
			'Ю' => "\x04\x2E",
			'Я' => "\x04\x2F",
			'а' => "\x04\x30",
			'б' => "\x04\x31",
			'в' => "\x04\x32",
			'г' => "\x04\x33",
			'д' => "\x04\x34",
			'е' => "\x04\x35",
			'ё' => "\x04\x51",
			'ж' => "\x04\x36",
			'з' => "\x04\x37",
			'и' => "\x04\x38",
			'й' => "\x04\x39",
			'к' => "\x04\x3A",
			'л' => "\x04\x3B",
			'м' => "\x04\x3C",
			'н' => "\x04\x3D",
			'о' => "\x04\x3E",
			'п' => "\x04\x3F",
			'р' => "\x04\x40",
			'с' => "\x04\x41",
			'т' => "\x04\x42",
			'у' => "\x04\x43",
			'ф' => "\x04\x44",
			'х' => "\x04\x45",
			'ц' => "\x04\x46",
			'ч' => "\x04\x47",
			'ш' => "\x04\x48",
			'щ' => "\x04\x49",
			'ь' => "\x04\x4A",
			'ы' => "\x04\x4B",
			'ъ' => "\x04\x4C",
			'э' => "\x04\x4D",
			'ю' => "\x04\x4E",
			'я' => "\x04\x4F",
			// all \x2? removed
			// all \x3? removed
			'¡' => "\x40",
			'Ä' => "\x5B",
			'Ö' => "\x5C",
			'Ñ' => "\x5D",
			'Ü' => "\x5E",
			'§' => "\x5F",
			'¿' => "\x60",
			'ä' => "\x7B",
			'ö' => "\x7C",
			'ñ' => "\x7D",
			'ü' => "\x7E",
			'à' => "\x7F",
			'^' => "\x1B\x14",
			'{' => "\x1B\x28",
			'}' => "\x1B\x29",
			'\\' => "\x1B\x2F",
			'[' => "\x1B\x3C",
			'~' => "\x1B\x3D",
			']' => "\x1B\x3E",
			'|' => "\x1B\x40",
			'€' => "\x1B\x65",
			// UCS2-BE polish characters
			'Ą' => "\x01\x04",
			'ą' => "\x01\x05",
			'Ć' => "\x01\x06",
			'ć' => "\x01\x07",
			'Ę' => "\x01\x18",
			'ę' => "\x01\x19",
			'Ł' => "\x01\x41",
			'ł' => "\x01\x42",
			'Ń' => "\x01\x43",
			'ń' => "\x01\x44",
			'Ó' => "\x00\xD3",
			'ó' => "\x00\xF3",
			'Ś' => "\x01\x5A",
			'ś' => "\x01\x5B",
			'Ż' => "\x01\x7B",
			'ż' => "\x01\x7C",
			'Ź' => "\x01\x79",
			'ź' => "\x01\x7A",
			// UCS2-BE sample symbols
			'🙁' => "\x26\x39",
			'🙂' => "\x26\x3a",
			'✌️' => "\x27\x0c",
			'✋' => "\x27\x0b",
			'✊' => "\x27\x0a",
		];
		// $converted = strtr($string, $dict);

		// Replace unconverted UTF-8 chars from codepages U+0080-U+07FF, U+0080-U+FFFF and U+010000-U+10FFFF with a single ?
		// return preg_replace('/([\\xC0-\\xDF].)|([\\xE0-\\xEF]..)|([\\xF0-\\xFF]...)/m','?',$converted);
		return strtr($string, $dict);
	}

	/**
	 * Count the number of GSM 03.38 chars a conversion would contain.
	 * It's about 3 times faster to count than convert and do strlen() if conversion is not required.
	 *
	 * @param string $utf8String
	 * @return integer
	 */
	public static function countGsm0338Length($utf8String)
	{
		$len = mb_strlen($utf8String, 'utf-8');
		$len += (int)preg_match_all('/[\\^{}\\\~€|\\[\\]]/mu', $utf8String, $m);
		return $len;
	}

	/**
	 * Pack an 8-bit string into 7-bit GSM format
	 * Returns the packed string in binary format
	 *
	 * @param string $data
	 * @return string
	 */
	public static function pack7bit($data)
	{
		$l = strlen($data);
		$currentByte = 0;
		$offset = 0;
		$packed = '';
		for ($i = 0; $i < $l; $i++) {
			// cap off any excess bytes
			$septet = ord($data[$i]) & 0x7f;
			// append the septet and then cap off excess bytes
			$currentByte |= ($septet << $offset) & 0xff;
			// update offset
			$offset += 7;

			if ($offset > 7) {
				// the current byte is full, add it to the encoded data.
				$packed .= chr($currentByte);
				// shift left and append the left shifted septet to the current byte
				$currentByte = $septet = $septet >> (15 - $offset); // same as (7 - ($offset - 8))
				// update offset
				$offset -= 8; // 7 - (7 - ($offset - 8))
			}
		}
		if ($currentByte > 0) $packed .= chr($currentByte); // append the last byte

		return $packed;
	}
}
