<?php

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Http\Exceptions\Http404Exception;
use Kohana\Http\Exceptions\RequestException;
use Kohana\Support\Feed;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for feed helper
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.feed')]
class Kohana_FeedTest extends TestCase
{
    /**
     * Sets up the environment
     */
    // @codingStandardsIgnoreStart
    public function setUp(): void
    // @codingStandardsIgnoreEnd
    {
        parent::setUp();
        Application::$config->load('url')->set('trusted_hosts', ['localhost']);
    }

    /**
     * Provides test data for test_parse()
     *
     * @return array
     */
    public static function provider_parse(): array
    {
        return [
            // $source, $expected
            [
                realpath(KOHANA_FIXTURES . '/feeds/activity.atom'),
                [
                    'Proposals (Political/Workflow) #4839 (New)',
                    'Proposals (Political/Workflow) #4782',
                ],
            ],
            [
                realpath(KOHANA_FIXTURES . '/feeds/example.rss20'),
                [
                    'Example entry',
                ],
            ],
        ];
    }

    /**
     * Tests that Feed::parse gets the correct number of elements
     *
     * @param string $source URL to test
     * @param $expected_titles
     * @throws Http404Exception
     * @throws KohanaException
     * @throws RequestException
     */
    #[DataProvider('provider_parse')]
    #[Test]
    public function test_parse(string $source, $expected_titles)
    {
        $titles = [];
        foreach (Feed::parse($source) as $item) {
            $titles[] = $item['title'];
        }

        $this->assertSame($expected_titles, $titles);
    }

    /**
     * Provides test data for test_create()
     *
     * @return array
     */
    public static function provider_create(): array
    {
        $info = [
            'pubDate' => 123,
            'image' => [
                'link' => 'https://kohana.top/image.png',
                'url' => 'https://kohana.top/',
                'title' => 'title',
            ],
        ];

        return [
            [
                $info,
                ['foo' => ['foo' => 'bar', 'pubDate' => 123, 'link' => 'foo']],
                ['_SERVER' => ['HTTP_HOST' => 'localhost'] + $_SERVER],
                ['channel > item > foo', 'bar'],
                [
                    static::matcher_composer($info, 'image', 'link'),
                    static::matcher_composer($info, 'image', 'url'),
                    static::matcher_composer($info, 'image', 'title'),
                ],
            ],
        ];
    }

    /**
     * Helper for handy matcher composing
     *
     * @param array $data
     * @param string $tag
     * @param string $child
     * @return array
     */
    private static function matcher_composer(array $data, string $tag, string $child): array
    {
        return [
            'channel > ' . $tag . ' > ' . $child,
            $data[$tag][$child],
        ];
    }

    /**
     *
     *
     *
     * @param array $info info to pass
     * @param array $items items to add
     * @param array $enviroment Server environment data.
     * @param array $matcher_item XML matcher structure for the main item.
     * @param array $matchers_image Array of XML matcher structures for image elements.
     * @throws KohanaException
     * @throws ReflectionException
     */
    #[DataProvider('provider_create')]
    #[Test]
    public function test_create(array $info, array $items, array $enviroment, array $matcher_item, array $matchers_image)
    {
        $this->setEnvironment($enviroment);

        $this->assertSelectEquals($matcher_item[0], $matcher_item[1], true, Feed::create($info, $items), '', false);

        foreach ($matchers_image as $matcher_image) {
            $this->assertSelectEquals($matcher_image[0], $matcher_image[1], true, Feed::create($info, $items), '', false);
        }
    }

}
