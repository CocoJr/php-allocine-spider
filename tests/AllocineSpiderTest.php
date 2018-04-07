<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace CocoJr\Allocine\Tests;

use CocoJr\Allocine\AllocineSpider;
use CocoJr\Allocine\FilmSpider;
use CocoJr\Allocine\PersonSpider;
use PHPUnit\Framework\TestCase;

/**
 * Class AllocineSpiderTest
 */
class AllocineSpiderTest extends TestCase
{
    /**
     * @dataProvider requestProvider
     */
    public function testMakeRequest($allocine_id, $type, $callback = null, $exceptException = null)
    {
        $spider = new AllocineSpider();

        if ($exceptException) {
            $this->expectException($exceptException);
        }

        $html = $this->invokeMethod($spider, 'makeRequest', array($allocine_id, $type));

        if ($callback && !$exceptException) {
            $this->$callback($html);
        }
    }

    /**
     * Provider for testMakeRequest
     * Test 3 types: film, person, serie
     * Test default template of each types and not found
     *
     * @return array
     */
    public function requestProvider()
    {
        return [
            // Check not found page
            [0, 'film', 'cbNotFound', null],
            [0, 'person', 'cbNotFound', null],
            // Check complete card FilmSpider and PersonSpider
            [59809, 'film', 'cbFilmFound', null],
            [1192, 'person', 'cbPersonFound', null],
            // Check exception
            [0, 'non-exist', null, \Exception::class],
            [0, 'serie', null, \Exception::class],
            [1, 'non-exist', null, \Exception::class],
            [3517, 'serie', null, \Exception::class],
        ];
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @throws
     *
     * @return mixed Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Callback function for not found
     *
     * @param $html
     */
    private function cbNotFound($html)
    {
        $this->assertContains('HTTP/1.1 404 Not Found', $html);
        $this->assertRegExp('#<div class="title">\s+Erreur 404 - <span class="aka-error">Page introuvable</span>\s+</div>#isU', $html);
    }

    /**
     * Callback function called to check person card, using regex available in PersonSpider
     *
     * @param $html
     */
    private function cbFilmFound($html)
    {
        $this->assertContains('HTTP/1.1 200 OK', $html);
        $this->checkRegexPatterns($html, FilmSpider::REGEX);
    }

    /**
     * Callback function called to check person card, using regex available in PersonSpider
     *
     * @param $html
     */
    private function cbPersonFound($html)
    {
        $this->assertContains('HTTP/1.1 200 OK', $html);
        $this->checkRegexPatterns($html, PersonSpider::REGEX);
    }

    /**
     * Assert regex from html
     *
     * @param $html
     * @param $regexes
     */
    private function checkRegexPatterns($html, $regexes)
    {
        foreach ($regexes as $regex) {
            if (is_array($regex)) {
                foreach ($regex as $r) {
                    $this->assertRegExp($r, $html);
                }
            } else {
                $this->assertRegExp($regex, $html);
            }
        }
    }
}
