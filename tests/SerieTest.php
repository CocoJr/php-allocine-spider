<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace CocoJr\Allocine\Tests;

use CocoJr\Allocine\SerieSpider;
use PHPUnit\Framework\TestCase;

/**
 * Class FilmTest
 */
class SerieTest extends TestCase
{
    /**
     * @dataProvider fetchFilmProvider
     */
    public function testFetchSerie($allocine_id, $fullPerson = false, $callback = null)
    {
        $spider = new SerieSpider();

        $film = $spider->fetchSerie($allocine_id, $fullPerson);

        if ($callback) {
            $this->$callback($film);
        }
    }

    /**
     * Provider for testFetchFilm
     *
     * @return array
     */
    public function fetchFilmProvider()
    {
        return [
            [0, false, 'checkNotFound'],
            [0, true, 'checkNotFound'],
        ];
    }

    private function checkNotFound($film)
    {
        $this->assertFalse($film);
    }
}
