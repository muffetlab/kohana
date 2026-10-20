<?php

use Kohana\Kodoc;
use Kohana\Route;
use Kohana\URL;

/**
 * @group kohana
 * @group kohana.userguide
 *
 * @package    Kohana/Userguide
 * @author     Kohana Team
 * @copyright  (c) 2008-2013 Kohana Team
 * @license    https://kohana.top/license
 */
class Kohana_KodocTest extends PHPUnit\Framework\TestCase
{
    public function provider_parse_basic(): array
    {
        return [
            [
                <<<'COMMENT'
/**
 * Description
 */
COMMENT
                ,
                ["<p>Description</p>\n", []],
            ],
            [
                <<<'COMMENT'
/**
 * Description spanning
 * multiple lines
 */
COMMENT
                ,
                ["<p>Description spanning\nmultiple lines</p>\n", []],
            ],
            [
                <<<'COMMENT'
/**
 * Description including
 *
 *     a code block
 */
COMMENT
                ,
                ["<p>Description including</p>\n\n<pre><code>a code block\n</code></pre>\n", []],
            ],
            [
                <<<'COMMENT'
	/**
	 * Indented
	 */
COMMENT
                ,
                ["<p>Indented</p>\n", []],
            ],
            [
                <<<'COMMENT'
/**
 * @tag Content
 */
COMMENT
                ,
                ['', ['tag' => ['Content']]],
            ],
            [
                <<<'COMMENT'
/**
 * @tag Multiple
 * @tag Tags
 */
COMMENT
                ,
                ['', ['tag' => ['Multiple', 'Tags']]],
            ],
            [
                <<<'COMMENT'
/**
 * Description with tag
 * @tag Content
 */
COMMENT
                ,
                [
                    "<p>Description with tag</p>\n",
                    ['tag' => ['Content']],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @trailingspace
 */
COMMENT
                ,
                ['', ['trailingspace' => ['']]],
            ],
            [
                <<<'COMMENT'
/**
 * @tag Content that spans
 * multiple lines
 */
COMMENT
                ,
                [
                    '',
                    ['tag' => ["Content that spans\nmultiple lines"]],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @tag Content that spans
 *    multiple lines indented
 */
COMMENT
                ,
                [
                    '',
                    ['tag' => ["Content that spans\n   multiple lines indented"]],
                ],
            ],
        ];
    }

    /**
     * @covers          Kohana_Kodoc::parse
     *
     * @dataProvider    provider_parse_basic
     *
     * @param string $comment Argument to the method
     * @param array $expected Expected result
     * @throws Kohana_Exception
     */
    public function test_parse_basic(string $comment, array $expected)
    {
        $this->assertSame($expected, Kodoc::parse($comment));
    }

    public function provider_parse_tags(): array
    {
        $route_api = Route::get('docs/api');

        return [
            [
                <<<'COMMENT'
/**
 * @access public
 */
COMMENT
                ,
                ['', []],
            ],
            [
                <<<'COMMENT'
/**
 * @copyright Some plain text
 */
COMMENT
                ,
                ['', ['copyright' => ['Some plain text']]],
            ],
            [
                <<<'COMMENT'
/**
 * @copyright (c) 2008-2013 Kohana Team
 */
COMMENT
                ,
                ['', ['copyright' => ['&copy; 2008-2013 Kohana Team']]],
            ],
            [
                <<<'COMMENT'
/**
 * @license Kohana
 */
COMMENT
                ,
                ['', ['license' => ['Kohana']]],
            ],
            [
                <<<'COMMENT'
/**
 * @license https://kohana.top/license
 */
COMMENT
                ,
                ['', ['license' => ['<a href="https://kohana.top/license">https://kohana.top/license</a>']]],
            ],
            [
                <<<'COMMENT'
/**
 * @link https://kohana.top
 */
COMMENT
                ,
                ['', ['link' => ['<a href="https://kohana.top">https://kohana.top</a>']]],
            ],
            [
                <<<'COMMENT'
/**
 * @link https://kohana.top Description
 */
COMMENT
                ,
                ['', ['link' => ['<a href="https://kohana.top">Description</a>']]],
            ],
            [
                <<<'COMMENT'
/**
 * @see MyClass
 */
COMMENT
                ,
                [
                    '',
                    [
                        'see' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'MyClass'])) . '">MyClass</a>',
                        ],
                    ],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @see MyClass::method()
 */
COMMENT
                ,
                [
                    '',
                    [
                        'see' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'MyClass']) . '#method') . '">MyClass::method()</a>',
                        ],
                    ],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @see Kohana\Feed::parse()
 */
COMMENT
                ,
                [
                    '',
                    [
                        'see' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'Kohana\Feed']) . '#parse') . '">Kohana\Feed::parse()</a>',
                        ],
                    ],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @throws Exception
 */
COMMENT
                ,
                [
                    '',
                    [
                        'throws' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'Exception'])) . '">Exception</a>',
                        ],
                    ],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @throws Exception During failure
 */
COMMENT
                ,
                [
                    '',
                    [
                        'throws' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'Exception'])) . '">Exception</a> During failure',
                        ],
                    ],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @throws Kohana\Exception During failure
 */
COMMENT
                ,
                [
                    '',
                    [
                        'throws' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'Kohana\Exception'])) . '">Kohana\Exception</a> During failure',
                        ],
                    ],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @uses MyClass
 */
COMMENT
                ,
                [
                    '',
                    [
                        'uses' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'MyClass'])) . '">MyClass</a>',
                        ],
                    ],
                ],
            ],
            [
                <<<'COMMENT'
/**
 * @uses MyClass::method()
 */
COMMENT
                ,
                [
                    '',
                    [
                        'uses' => [
                            '<a href="' . URL::site($route_api->uri(['class' => 'MyClass']) . '#method') . '">MyClass::method()</a>',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @covers          Kohana_Kodoc::format_tag
     * @covers          Kohana_Kodoc::parse
     *
     * @dataProvider    provider_parse_tags
     *
     * @param string $comment Argument to the method
     * @param array $expected Expected result
     * @throws Kohana_Exception
     */
    public function test_parse_tags(string $comment, array $expected)
    {
        $this->assertSame($expected, Kodoc::parse($comment));
    }

    /**
     * Tests that classes listed for the API browser are namespaced
     *
     * @covers Kohana_Kodoc::classes
     */
    public function test_classes_are_namespaced()
    {
        // The list that Kohana::list_files('classes') would return
        $list = [
            'classes/Feed.php' => SYSPATH . 'classes/Feed.php',
            'classes/Kohana' => [
                'classes/Kohana/Exception.php' => SYSPATH . 'classes/Kohana/Exception.php',
            ],
            'classes/Controller' => [
                'classes/Controller/Welcome.php' => APPPATH . 'classes/Controller/Welcome.php',
            ],
        ];

        // Framework classes are in the Kohana namespace, application classes in App
        $this->assertSame([
            'Kohana\Feed' => 'Kohana\Feed',
            'Kohana\Kohana\Exception' => 'Kohana\Kohana\Exception',
            'App\Controller\Welcome' => 'App\Controller\Welcome',
        ], Kodoc::classes($list));
    }

    /**
     * Tests that short class names in tags are resolved to their class
     *
     * @covers Kohana_Kodoc::link_class_member
     */
    public function test_short_class_names_are_resolved()
    {
        $route_api = Route::get('docs/api');

        // A short name that exists in the framework is resolved
        $this->assertSame(
            '<a href="' . URL::site($route_api->uri(['class' => 'Kohana\Cookie']) . '#set') . '">Cookie::set</a>',
            Kodoc::format_tag('uses', 'Cookie::set')
        );

        // A short name that does not exist is left alone
        $this->assertSame(
            '<a href="' . URL::site($route_api->uri(['class' => 'MissingClass']) . '#method') . '">MissingClass::method()</a>',
            Kodoc::format_tag('uses', 'MissingClass::method()')
        );
    }

}
