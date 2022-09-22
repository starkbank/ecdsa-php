<?php

namespace EllipticCurve\Utils;

use DateTime;
use Exception;
use EllipticCurve\Utils\Oid;


class Der
{
    public static function encodeConstructed(...$encodedValues)
    {
        return Der::encodePrimitive(DerFieldType::$sequence, join("", $encodedValues));
    }
    
    public static function encodePrimitive($tagType, $value)
    {
        if ($tagType == DerFieldType::$integer)
            $value = Der::encodeInteger($value);
        if ($tagType == DerFieldType::$object)
            $value = Oid::oidToHex($value);
    
        $tag = TagCode::$typeToHexTag[$tagType];
        $size = Der::generateLengthBytes($value);
        return "{$tag}{$size}{$value}";
    }
    
    public static function parse($hexadecimal)
    {
        if (strlen($hexadecimal) == 0)
            return [];
        $typeByte = substr($hexadecimal, 0, 2);
        $hexadecimal = substr($hexadecimal, 2);
        list($length, $lengthBytes) = Der::readLengthBytes($hexadecimal);
        $content = substr($hexadecimal, $lengthBytes, $length);
        $hexadecimal = substr($hexadecimal, $lengthBytes + $length);
        if (strlen($content) < $length)
            throw new Exception("missing bytes in DER parse");
        
        $tagData = Der::getTagData($typeByte);
        if ($tagData["isConstructed"])
            $content = Der::parse($content);
        
        $valueParser = [
            DerFieldType::$null => Parser::$parseNull,
            DerFieldType::$object => Parser::$parseOid,
            DerFieldType::$utcTime => Parser::$parseTime,
            DerFieldType::$integer => Parser::$parseInteger,
            DerFieldType::$printableString => Parser::$parseString,
        ][$tagData["type"]] ?? Parser::$parseAny;
    
        return array_merge([Parser::$valueParser($content)], Der::parse($hexadecimal));
    }
    
    private static function encodeInteger($number)
    {
        $hexadecimal = Binary::hexFromInt(gmp_abs($number));
        if ($number < 0) {
            $bitCount = 4 * strlen($hexadecimal);
            $twosComplement = gmp_pow(2, $bitCount) + $number;
            return Binary::hexFromInt($twosComplement);
        }
        $bits = Binary::bitsFromHex($hexadecimal[0]);
        if ($bits[0] == "1") {   // If the first bit was left as 1, number would be parsed as a negative integer with two`s complement
            $hexadecimal = "00" . $hexadecimal;
        }
        return $hexadecimal;
    }
    
    private static function readLengthBytes($hexadecimal)
    {
        $lengthBytes = 2;
        $lengthIndicator = Binary::intFromHex(substr($hexadecimal, 0, $lengthBytes));
        $isShortForm = ($lengthIndicator < 128);  // checks if first bit of byte is 1 (a.k.a short-form)
        if ($isShortForm) {
            $length = ($lengthIndicator * 2);
            return [gmp_intval($length), gmp_intval($lengthBytes)];
        }
    
        $lengthLength = $lengthIndicator - 128;  # nullifies first bit of byte (only used as long-form flag)
        if ($lengthLength == 0)
            throw new Exception("indefinite length encoding located in DER");
        $lengthBytes += 2 * $lengthLength;
        $length = Binary::intFromHex(substr($hexadecimal, 2, gmp_intval($lengthBytes - 2))) * 2;
        return [gmp_intval($length), gmp_intval($lengthBytes)];
    }
    
    private static function generateLengthBytes($hexadecimal)
    {
        $size = intdiv(strlen($hexadecimal), 2);
        $length = Binary::hexFromInt($size);
        if ($size < 128)
            return str_pad($length, 2, "0", STR_PAD_LEFT);
        $lengthLength = 128 + intdiv(strlen($length), 2);
        return Binary::hexFromInt($lengthLength) + $length;
    }
    
    private static function getTagData($tag)
    {
        $bits = Binary::bitsFromHex($tag);
        $bit8 = $bits[0];
        $bit7 = $bits[1];
        $bit6 = $bits[2];
    
        $tagClass = [
            "0" => [
                "0" => "universal",
                "1" => "application",
            ],
            "1" => [
                "0" => "context-specific",
                "1" => "private",
            ],
        ][$bit8][$bit7];
        $isContructed = ($bit6 == "1");
    
        return [
            "class" => $tagClass,
            "isConstructed" => $isContructed,
            "type" => TagCode::$hexTagToType[$tag] ?? null,
        ];
    }
}


class DerFieldType
{
    public static $integer = "integer";
    public static $bitString = "bitString";
    public static $octetString = "octetString";
    public static $null = "null";
    public static $object = "object";
    public static $printableString = "printableString";
    public static $utcTime = "utcTime";
    public static $sequence = "sequence";
    public static $set = "set";
    public static $oidContainer = "oidContainer";
    public static $publicKeyPointContainer = "publicKeyPointContainer";
}


class TagCode
{
    public static $hexTagToType;
    public static $typeToHexTag;
}
TagCode::$hexTagToType = array(
    "02" => DerFieldType::$integer,
    "03" => DerFieldType::$bitString,
    "04" => DerFieldType::$octetString,
    "05" => DerFieldType::$null,
    "06" => DerFieldType::$object,
    "13" => DerFieldType::$printableString,
    "17" => DerFieldType::$utcTime,
    "30" => DerFieldType::$sequence,
    "31" => DerFieldType::$set,
    "a0" => DerFieldType::$oidContainer,
    "a1" => DerFieldType::$publicKeyPointContainer
);
foreach (TagCode::$hexTagToType as $key=>$value) {
    TagCode::$typeToHexTag[$value] = $key;
}


class Parser
{
    public static $parseAny = "parseAny";
    public static $parseTime = "parseTime";
    public static $parseString = "parseString";
    public static $parseOid = "parseOid";
    public static $parseNull = "parseNull";
    public static $parseInteger = "parseInteger";

    public static function parseInteger($hexadecimal)
    {
        $integer = Binary::intFromHex($hexadecimal);
        $bits = Binary::bitsFromHex($hexadecimal[0]);
        if ($bits[0] == "0") {
            return $integer;
        }
        $bitCount = 4 * strlen($hexadecimal);
        return gmp_sub($integer, gmp_pow(2, $bitCount));
    }

    public static function parseAny($content)
    {
        return $content;
    }
    
    public static function parseOid($hexadecimal)
    {
        return Oid::oidFromHex($hexadecimal);
    }
    
    public static function parseTime($hexadecimal)
    {
        $string = Parser::parseString($hexadecimal);
        return DateTime::createFromFormat("ymdHis\Z", $string);
    }
    
    public static function parseString($hexadecimal)
    {
        return Binary::byteStringFromHex($hexadecimal);
    }
    
    public static function parseNull($_content)
    {
        return null;
    }
}
