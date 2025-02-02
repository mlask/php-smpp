<?php
namespace smpp\helpers;

class GsmEncoderHelper
{
	const GSM0338_DICT = [
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
	
	public static function utf8_to_gsm0338 (string $string): string
	{
		return strtr($string, self::GSM0338_DICT);
	}
	
	public static function gsm0338_to_utf8 (string $string): string
	{
		return strtr($string, array_flip(self::GSM0338_DICT));
	}
}