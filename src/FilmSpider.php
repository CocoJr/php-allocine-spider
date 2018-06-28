<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace CocoJr\Allocine;

/**
 * Class FilmSpider
 */
class FilmSpider extends AllocineSpider
{
    const REGEX = array(
        'card' => '#<div class="card card-entity card-movie-overview row row-col-padded-10 cf"\s*>(.+)</div>#s',
        'title' => '#<title>(.+) (- film [0-9]+ )?- AlloCiné</title>#U',
        'synopsis' => '#<div class=".+" itemprop="description">(.+)</div>#isU',
        'img' => '#<img class="thumbnail-img" src="(.+)" alt=".+" width="(215|216)" height="(290|288)" itemprop="image"\s*/>#U',
        'release' => '#<title>(.+) - film ([0-9]+) - AlloCiné</title>#U',
        'duration' => array(
            '#\((([0-9]+h )?([0-9]+min)?)#',
            '#([0-9]+)h#',
            '#([0-9]+)min#'
        ),
        'creators' => '#<span itemprop="director" itemscope itemtype="http://schema.org/Person">\s+<a class="blue-link" href="/personne/fichepersonne_gen_cpersonne=([0-9]+).html" title=".+" itemprop="url"><span itemprop="name">(.+)</span></a>#U',
        'actors' => '#<(?:strong|div) class="meta-title" itemprop="actor" itemscope itemtype="http://schema.org/Person">\s+<a class="meta-title-link" href="/personne/fichepersonne_gen_cpersonne=([0-9]+).html">\s+<span itemprop="name">(.+)</span>\s+</a>#U',
        'types' => '#<span itemprop="genre">(.+)</span>#U',
        'nationalities' => array(
            '#<div class="meta-body-item">\s+<span class=".+">Nationalités?</span>(.+)</div>#isU',
            '#<span class=".+nationality">(.+)</span>#U',
        )
    );

    /**
     * Fetch film from allocine.fr
     *
     * Return:
     * {
     *   title: bool|string,
     *   synopsis: bool|string,
     *   img: bool|string,
     *   release_date: bool|\DateTime,
     *   duration: bool|int,
     *   creators: array(id: int, name: string),
     *   actors: array(id: int, name: string),
     *   types: array(string),
     *   nationalities: array(string)
     * }
     *
     * @param int $allocine_id The id of the film in allocine.fr
     *
     * @throws \Exception
     *
     * @return bool|\StdClass
     */
    public function fetchFilm($allocine_id, $fullPerson = false)
    {
        $film = false;
        $html = $this->makeRequest($allocine_id, 'film');

        if ($title = $this->getFilmTitle($html)) {
            preg_match($this::REGEX['card'], $html, $card);
            $card = $card[1];

            $film = new \StdClass;
            $film->allocineId = $allocine_id;
            $film->title = $title;
            $film->synopsis = $this->getFilmSynopsis($html);
            $film->img = $this->getFilmImg($card);
            $film->releaseDate = $this->getFilmReleaseDate($html);
            $film->duration = $this->getFilmDuration($card);
            $film->creators = $this->getFilmCreators($card);
            $film->actors = $this->getFilmActors($html);
            $film->types = $this->getFilmTypes($html);
            $film->nationalities = $this->getFilmNationalities($card);

            if ($fullPerson) {
                $personSpider = new PersonSpider();

                foreach ($film->creators as $index => $creator) {
                    if ($creator = $personSpider->fetchPerson($creator->allocineId)) {
                        $film->creators[$index] = $creator;
                    } else {
                        $film->creators[$index]->nationality = null;
                        $film->creators[$index]->birthDate = null;
                        $film->creators[$index]->img = null;
                        $film->creators[$index]->biography = null;
                    }
                }

                foreach ($film->actors as $index => $actor) {
                    if ($actor = $personSpider->fetchPerson($actor->allocineId)) {
                        $film->actors[$index] = $actor;
                    } else {
                        $film->actors[$index]->nationality = array();
                        $film->actors[$index]->birthDate = null;
                        $film->actors[$index]->img = null;
                        $film->actors[$index]->biography = null;
                    }
                }
            }
        }

        return $film;
    }

    /**
     * Get film title
     *
     * @param string $card The html of allocine page
     *
     * @return bool|string
     */
    private function getFilmTitle($card)
    {
        $title = false;
        if (preg_match($this::REGEX['title'], $card, $tmp_title)) {
            $title = trim($tmp_title[1]);
        }

        return $title;
    }

    /**
     * Get film synopsis
     *
     * @param string $card The html of allocine page
     *
     * @return null|string
     */
    private function getFilmSynopsis($card)
    {
        $synopsis = null;
        if (preg_match($this::REGEX['synopsis'], $card, $tmp_synopsis)) {
            $synopsis = trim($tmp_synopsis[1]);
        }

        return $synopsis;
    }

    /**
     * Get film thumbnail
     *
     * @param string $card The html of allocine page
     *
     * @return null
     */
    private function getFilmImg($card)
    {
        $img = null;
        if (preg_match($this::REGEX['img'], $card, $tmp_img)) {
            $img = $tmp_img[1];
        }

        return $img;
    }

    /**
     * Get film release date
     *
     * @param string $card The html of allocine page
     *
     * @return \DateTime|null
     */
    private function getFilmReleaseDate($card)
    {
        $release_date = null;
        if (preg_match($this::REGEX['release'], $card, $tmp_release_date)) {
            $release_date = $tmp_release_date[2];
            $release_date = new \DateTime($release_date.'-01-01 00:00:00');
        }

        return $release_date;
    }

    /**
     * get film duration
     *
     * @param string $card The html of allocine page
     *
     * @return int|null
     */
    private function getFilmDuration($card)
    {
        $duration = null;
        if (preg_match($this::REGEX['duration'][0], $card, $tmp_duration)) {
            $tmp_duration = $tmp_duration[1];
            $duration = 0;
            if (preg_match($this::REGEX['duration'][1], $tmp_duration, $duration_hours)) {
                $duration += $duration_hours[1] * 60;
            }
            if (preg_match($this::REGEX['duration'][2], $tmp_duration, $duration_minutes)) {
                $duration += $duration_minutes[1];
            }

            if (empty($duration)) {
                $duration = null;
            }
        }

        return $duration;
    }

    /**
     * Get film creators
     *
     * @param string $card The html of allocine page
     *
     * @return array
     */
    private function getFilmCreators($card)
    {
        $creators = array();
        preg_match_all($this::REGEX['creators'], $card, $tmp_creators);
        foreach ($tmp_creators[0] as $creator) {
            preg_match($this::REGEX['creators'], $creator, $tmp_creator);
            $std_creator = new \StdClass();
            $std_creator->allocineId = trim($tmp_creator[1]);
            $std_creator->name = trim($tmp_creator[2]);
            $creators[] = $std_creator;
        }

        return $creators;
    }

    /**
     * Get film actors
     *
     * @param string $card The html of allocine page
     *
     * @return array
     */
    private function getFilmActors($card)
    {
        $actors = array();
        preg_match_all($this::REGEX['actors'], $card, $tmp_actors);

        foreach ($tmp_actors[0] as $actor) {
            preg_match($this::REGEX['actors'], $actor, $tmp_actor);
            $std_creator = new \StdClass();
            $std_creator->allocineId = trim($tmp_actor[1]);
            $std_creator->name = trim($tmp_actor[2]);
            $actors[] = $std_creator;
        }

        return $actors;
    }

    /**
     * Get film types
     *
     * @param string $card The html of allocine page
     *
     * @return array
     */
    private function getFilmTypes($card)
    {
        $types = array();
        preg_match_all($this::REGEX['types'], $card, $film_types);
        foreach ($film_types[0] as $type) {
            preg_match($this::REGEX['types'], $type, $tmp_type);
            $types[] = trim($tmp_type[1]);
        }

        return $types;
    }

    /**
     * Get film nationalities
     *
     * @param string $card The html of allocine page
     *
     * @return array
     */
    private function getFilmNationalities($card)
    {
        $nationalities = array();
        if (preg_match($this::REGEX['nationalities'][0], $card, $film_nationalities)) {
            preg_match_all($this::REGEX['nationalities'][1], $film_nationalities[1], $film_nationalities);
            foreach ($film_nationalities[1] as $nationality) {
                $nationalities[] = ucfirst(trim($nationality));
            }
        }

        return $nationalities;
    }
}