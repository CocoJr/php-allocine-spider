<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace CocoJr\Allocine;

/**
 * Class PersonSpider
 */
class SerieSpider extends AllocineSpider
{
    /**
     * @TODO
     * Fetch serie from allocine.fr
     *
     * @param int $allocine_id The id of the serie in allocine.fr
     *
     * @return bool|\StdClass
     */
    public function fetchSerie($allocine_id)
    {
        $serie = false;

        $saisons = $this->fetchSaisons($serie);
        $episodes = $this->fetchEpisodes($saisons);

        return $serie;
    }

    /**
     * @TODO
     *
     * @param $serie
     */
    public function fetchSaisons($serie)
    {

    }

    /**
     * @TODO
     *
     * @param $saison
     */
    public function fetchEpisodes($saison)
    {

    }
}