<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Kohana\Testing\TestCase;
use Kohana\Support\Utf8;
use Kohana\Support\Utf8Exception;

/**
 * Tests Kohana_UTF8 class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.utf8')]
class Kohana_UTF8Test extends TestCase
{
    /**
     * Provides test data for test_clean()
     */
    public static function provider_clean(): array
    {
        return [
            ["\0", ''],
            ["→foo\021", '→foo'],
            ["\x7Fbar", 'bar'],
            ["\xFF", ''],
            ["\x41", 'A'],
            [["→foo\021", "\x41"], ['→foo', 'A']],
        ];
    }

    /**
     * Tests UTF8::clean
     */
    #[DataProvider('provider_clean')]
    #[Test]
    public function test_clean($input, $expected)
    {
        $this->assertSame($expected, Utf8::clean($input));
    }

    /**
     * Provides test data for test_is_ascii()
     */
    public static function provider_is_ascii(): array
    {
        return [
            ["\0", true],
            ["\$eno\r", true],
            ['Señor', false],
            [['Se', 'nor'], true],
            [['Se', 'ñor'], false],
        ];
    }

    /**
     * Tests UTF8::is_ascii
     */
    #[DataProvider('provider_is_ascii')]
    #[Test]
    public function test_is_ascii($input, $expected)
    {
        $this->assertSame($expected, Utf8::isAscii($input));
    }

    /**
     * Provides test data for test_strip_ascii_ctrl()
     */
    public static function provider_strip_ascii_ctrl(): array
    {
        return [
            ["\0", ''],
            ["→foo\021", '→foo'],
            ["\x7Fbar", 'bar'],
            ["\xFF", "\xFF"],
            ["\x41", 'A'],
        ];
    }

    /**
     * Tests UTF8::strip_ascii_ctrl
     */
    #[DataProvider('provider_strip_ascii_ctrl')]
    #[Test]
    public function test_strip_ascii_ctrl($input, $expected)
    {
        $this->assertSame($expected, Utf8::stripAsciiCtrl($input));
    }

    /**
     * Provides test data for test_strip_non_ascii()
     */
    public static function provider_strip_non_ascii(): array
    {
        return [
            ["\0\021\x7F", "\0\021\x7F"],
            ['I ♥ cocoñùт', 'I  coco'],
        ];
    }

    /**
     * Tests UTF8::strip_non_ascii
     */
    #[DataProvider('provider_strip_non_ascii')]
    #[Test]
    public function test_strip_non_ascii($input, $expected)
    {
        $this->assertSame($expected, Utf8::stripNonAscii($input));
    }

    /**
     * Provides test data for test_transliterate_to_ascii()
     */
    public static function provider_transliterate_to_ascii(): array
    {
        return [
            ['Cocoñùт', -1, 'Coconuт'],
            ['COCOÑÙТ', -1, 'COCOÑÙТ'],
            ['Cocoñùт', 0, 'Coconuт'],
            ['COCOÑÙТ', 0, 'COCONUТ'],
            ['Cocoñùт', 1, 'Cocoñùт'],
            ['COCOÑÙТ', 1, 'COCONUТ'],
        ];
    }

    /**
     * Tests UTF8::transliterate_to_ascii
     */
    #[DataProvider('provider_transliterate_to_ascii')]
    #[Test]
    public function test_transliterate_to_ascii($input, $case, $expected)
    {
        $this->assertSame($expected, Utf8::transliterateToAscii($input, $case));
    }

    /**
     * Provides test data for test_strlen()
     */
    public static function provider_strlen(): array
    {
        return [
            ['Cocoñùт', 7],
            ['Coconut', 7],
        ];
    }

    /**
     * Tests UTF8::strlen
     */
    #[DataProvider('provider_strlen')]
    #[Test]
    public function test_strlen($input, $expected)
    {
        $this->assertSame($expected, Utf8::strlen($input));
    }

    /**
     * Provides test data for test_strpos()
     */
    public static function provider_strpos(): array
    {
        return [
            ['Cocoñùт', 'o', 0, 1],
            ['Cocoñùт', 'ñ', 1, 4],
        ];
    }

    /**
     * Tests UTF8::strpos
     */
    #[DataProvider('provider_strpos')]
    #[Test]
    public function test_strpos($input, $str, $offset, $expected)
    {
        $this->assertSame($expected, Utf8::strpos($input, $str, $offset));
    }

    /**
     * Provides test data for test_strrpos()
     */
    public static function provider_strrpos(): array
    {
        return [
            ['Cocoñùт', 'o', 0, 3],
            ['Cocoñùт', 'ñ', 2, 4],
        ];
    }

    /**
     * Tests UTF8::strrpos
     */
    #[DataProvider('provider_strrpos')]
    #[Test]
    public function test_strrpos($input, $str, $offset, $expected)
    {
        $this->assertSame($expected, Utf8::strrpos($input, $str, $offset));
    }

    /**
     * Provides test data for test_substr()
     */
    public static function provider_substr(): array
    {
        return [
            ['Cocoñùт', 3, 2, 'oñ'],
            ['Cocoñùт', 3, 9, 'oñùт'],
            ['Cocoñùт', 3, null, 'oñùт'],
            ['Cocoñùт', 3, -2, 'oñ'],
        ];
    }

    /**
     * Tests UTF8::substr
     */
    #[DataProvider('provider_substr')]
    #[Test]
    public function test_substr($input, $offset, $length, $expected)
    {
        $this->assertSame($expected, Utf8::substr($input, $offset, $length));
    }

    /**
     * Provides test data for test_substr_replace()
     */
    public static function provider_substr_replace(): array
    {
        return [
            ['Cocoñùт', 'šš', 3, 2, 'Cocššùт'],
            ['Cocoñùт', 'šš', 3, 9, 'Cocšš'],
        ];
    }

    /**
     * Tests UTF8::substr_replace
     */
    #[DataProvider('provider_substr_replace')]
    #[Test]
    public function test_substr_replace($input, $replacement, $offset, $length, $expected)
    {
        $this->assertSame($expected, Utf8::substrReplace($input, $replacement, $offset, $length));
    }

    /**
     * Provides test data for test_strtolower()
     */
    public static function provider_strtolower(): array
    {
        return [
            ['COCOÑÙТ', 'cocoñùт'],
            ['JÄGER', 'jäger'],
        ];
    }

    /**
     * Tests UTF8::strtolower
     */
    #[DataProvider('provider_strtolower')]
    #[Test]
    public function test_strtolower($input, $expected)
    {
        $this->assertSame($expected, Utf8::strtolower($input));
    }

    /**
     * Provides test data for test_strtoupper()
     */
    public static function provider_strtoupper(): array
    {
        return [
            ['Cocoñùт', 'COCOÑÙТ'],
            ['jäger', 'JÄGER'],
        ];
    }

    /**
     * Tests UTF8::strtoupper
     */
    #[DataProvider('provider_strtoupper')]
    #[Test]
    public function test_strtoupper($input, $expected)
    {
        $this->assertSame($expected, Utf8::strtoupper($input));
    }

    /**
     * Provides test data for test_ucfirst()
     */
    public static function provider_ucfirst(): array
    {
        return [
            ['ñùт', 'Ñùт'],
        ];
    }

    /**
     * Tests UTF8::ucfirst
     */
    #[DataProvider('provider_ucfirst')]
    #[Test]
    public function test_ucfirst($input, $expected)
    {
        $this->assertSame($expected, Utf8::ucfirst($input));
    }

    /**
     * Provides test data for test_strip_non_ascii()
     */
    public static function provider_ucwords(): array
    {
        return [
            ['ExAmple', 'ExAmple'],
            ['i ♥ Cocoñùт', 'I ♥ Cocoñùт'],
        ];
    }

    /**
     * Tests UTF8::ucwords
     */
    #[DataProvider('provider_ucwords')]
    #[Test]
    public function test_ucwords($input, $expected)
    {
        $this->assertSame($expected, Utf8::ucwords($input));
    }

    /**
     * Provides test data for test_strcasecmp()
     */
    public static function provider_strcasecmp(): array
    {
        return [
            ['Cocoñùт', 'Cocoñùт', 0],
            ['Čau', 'Čauo', -1],
            ['Čau', 'Ča', 1],
            ['Cocoñùт', 'Cocoñ', 1],
            ['Cocoñùт', 'Coco', 1],
        ];
    }

    /**
     * Tests UTF8::strcasecmp
     */
    #[DataProvider('provider_strcasecmp')]
    #[Test]
    public function test_strcasecmp($input, $input2, $expected)
    {
        $this->assertSame($expected, Utf8::strcasecmp($input, $input2));
    }

    /**
     * Provides test data for test_str_ireplace()
     */
    public static function provider_str_ireplace(): array
    {
        return [
            ['т', 't', 'cocoñuт', 'cocoñut'],
            ['Ñ', 'N', 'cocoñuт', 'cocoNuт'],
            [['т', 'Ñ', 'k' => 'k'], ['t', 'N', 'K'], ['cocoñuт'], ['cocoNut']],
            [['ñ'], 'n', 'cocoñuт', 'coconuт'],
        ];
    }

    /**
     * Tests UTF8::str_ireplace
     */
    #[DataProvider('provider_str_ireplace')]
    #[Test]
    public function test_str_ireplace($search, $replace, $subject, $expected)
    {
        $this->assertSame($expected, Utf8::strIreplace($search, $replace, $subject));
    }

    /**
     * Provides test data for test_stristr()
     */
    public static function provider_stristr(): array
    {
        return [
            ['Cocoñùт', 'oñ', 'oñùт'],
            ['Cocoñùт', 'o', 'ocoñùт'],
            ['Cocoñùт', 'k', false],
        ];
    }

    /**
     * Tests UTF8::stristr
     */
    #[DataProvider('provider_stristr')]
    #[Test]
    public function test_stristr($input, $input2, $expected)
    {
        $this->assertSame($expected, Utf8::stristr($input, $input2));
    }

    /**
     * Provides test data for test_strspn()
     */
    public static function provider_strspn(): array
    {
        return [
            ["foo", "o", 1, 2, 2],
            ['Cocoñùт', 'oñ', null, null, 1],
            ['Cocoñùт', 'oñ', 2, 4, 1],
            ['Cocoñùт', 'šš', 3, 9, 4],
        ];
    }

    /**
     * Tests UTF8::strspn
     */
    #[DataProvider('provider_strspn')]
    #[Test]
    public function test_strspn($input, $mask, $offset, $length, $expected)
    {
        $this->assertSame($expected, Utf8::strspn($input, $mask, $offset, $length));
    }

    /**
     * Provides test data for test_strcspn()
     */
    public static function provider_strcspn(): array
    {
        return [
            ['Cocoñùт', 'oñ', null, null, 1],
            ['Cocoñùт', 'oñ', 2, 4, 1],
            ['Cocoñùт', 'šš', 3, 9, 4],
        ];
    }

    /**
     * Tests UTF8::strcspn
     */
    #[DataProvider('provider_strcspn')]
    #[Test]
    public function test_strcspn($input, $mask, $offset, $length, $expected)
    {
        $this->assertSame($expected, Utf8::strcspn($input, $mask, $offset, $length));
    }

    /**
     * Provides test data for test_str_pad()
     */
    public static function provider_str_pad(): array
    {
        return [
            ['Cocoñùт', 10, 'š', STR_PAD_RIGHT, 'Cocoñùтššš'],
            ['Cocoñùт', 10, 'š', STR_PAD_LEFT, 'šššCocoñùт'],
            ['Cocoñùт', 10, 'š', STR_PAD_BOTH, 'šCocoñùтšš'],
        ];
    }

    /**
     * Tests UTF8::str_pad
     */
    #[DataProvider('provider_str_pad')]
    #[Test]
    public function test_str_pad($input, $length, $pad, $type, $expected)
    {
        $this->assertSame($expected, Utf8::strPad($input, $length, $pad, $type));
    }

    /**
     * Tests UTF8::str_pad error
     */
    #[Test]
    public function test_str_pad_error()
    {
        $this->expectException(Utf8Exception::class);

        Utf8::strPad('Cocoñùт', 10, 'š', 15);
    }

    /**
     * Provides test data for test_str_split()
     */
    public static function provider_str_split(): array
    {
        return [
            ['Bár', 1, ['B', 'á', 'r']],
            ['Cocoñùт', 2, ['Co', 'co', 'ñù', 'т']],
            ['Cocoñùт', 3, ['Coc', 'oñù', 'т']],
        ];
    }

    /**
     * Tests UTF8::str_split
     */
    #[DataProvider('provider_str_split')]
    #[Test]
    public function test_str_split($input, $split_length, $expected)
    {
        $this->assertSame($expected, Utf8::strSplit($input, $split_length));
    }

    /**
     * Provides test data for test_strrev()
     */
    public static function provider_strrev(): array
    {
        return [
            ['Cocoñùт', 'тùñocoC'],
        ];
    }

    /**
     * Tests UTF8::strrev
     */
    #[DataProvider('provider_strrev')]
    #[Test]
    public function test_strrev($input, $expected)
    {
        $this->assertSame($expected, Utf8::strrev($input));
    }

    /**
     * Provides test data for test_trim()
     */
    public static function provider_trim(): array
    {
        return [
            [' bar ', null, 'bar'],
            ['bar', 'b', 'ar'],
            ['barb', 'b', 'ar'],
        ];
    }

    /**
     * Tests UTF8::trim
     */
    #[DataProvider('provider_trim')]
    #[Test]
    public function test_trim($input, $input2, $expected)
    {
        $this->assertSame($expected, Utf8::trim($input, $input2));
    }

    /**
     * Provides test data for test_ltrim()
     */
    public static function provider_ltrim(): array
    {
        return [
            [' bar ', null, 'bar '],
            ['bar', 'b', 'ar'],
            ['barb', 'b', 'arb'],
            ['ñùт', 'ñ', 'ùт'],
        ];
    }

    /**
     * Tests UTF8::ltrim
     */
    #[DataProvider('provider_ltrim')]
    #[Test]
    public function test_ltrim($input, $charlist, $expected)
    {
        $this->assertSame($expected, Utf8::ltrim($input, $charlist));
    }

    /**
     * Provides test data for test_rtrim()
     */
    public static function provider_rtrim(): array
    {
        return [
            [' bar ', null, ' bar'],
            ['bar', 'b', 'bar'],
            ['barb', 'b', 'bar'],
            ['Cocoñùт', 'т', 'Cocoñù'],
        ];
    }

    /**
     * Tests UTF8::rtrim
     */
    #[DataProvider('provider_rtrim')]
    #[Test]
    public function test_rtrim($input, $input2, $expected)
    {
        $this->assertSame($expected, Utf8::rtrim($input, $input2));
    }

    /**
     * Provides test data for test_ord()
     */
    public static function provider_ord(): array
    {
        return [
            ['f', 102],
            ['ñ', 241],
            ['Ñ', 209],
        ];
    }

    /**
     * Tests UTF8::ord
     */
    #[DataProvider('provider_ord')]
    #[Test]
    public function test_ord($input, $expected)
    {
        $this->assertSame($expected, Utf8::ord($input));
    }

}
