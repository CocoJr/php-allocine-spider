<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2017 Thibault Colette
 */

namespace AppBundle\Service;

/**
 * Class AllocineService
 *
 * A spider for allocine.fr
 * Only for personal use
 *
 * Need curl extension. See http://php.net/manual/en/book.curl.php
 */
class AllocineService
{
    /** @const The hash of empty image in allocine.fr: you can use it to check if is the default empty image with md5_file */
    const NO_IMG_MD5 = array(
        '5cddb87648002a3a63e20409504f026d',
        'caaaccdaceca7223f2acaec2a31fb7c9',
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
     * @return bool|\StdClass
     */
    public function fetchFilm($allocine_id)
    {
        $film = false;
        $html = $this->makeRequest($allocine_id, 'film');

        if ($title = $this->getFilmTitle($html)) {
            preg_match('#<div class="card card-entity card-movie-overview row row-col-padded-10 cf"\s*>(.+)</div>#s', $html, $card);
            $card = $card[1];

            $film = new \StdClass;
            $film->title = $title;
            $film->synopsis = $this->getFilmSynopsis($html);
            $film->img = $this->getFilmImg($card);
            $film->release_date = $this->getFilmReleaseDate($html);
            $film->duration = $this->getFilmDuration($card);
            $film->creators = $this->getFilmCreators($card);
            $film->actors = $this->getFilmActors($html);
            $film->types = $this->getFilmTypes($html);
            $film->nationalities = $this->getFilmNationalities($card);
        }

        return $film;
    }

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

        return $serie;
    }

    /**
     * Fetch person from allocine.fr
     *
     * @param int $allocine_id The ID of person in Allocine.fr
     *
     * @return bool|\StdClass
     */
    public function fetchPerson($allocine_id)
    {
        $person = false;
        $html = $this->makeRequest($allocine_id, 'person');

        if (preg_match('#<ul class="list_item_p2v tab_col_first">(.+)</ul>#Us', $html, $card)) {
            $card = $card[1];

            $person = new \StdClass;
            $person->birthdate = $this->getPersonBirthdate($card);
            $person->img = $this->getPersonImg($html);
            $person->nationality = $this->getPersonNationality($card);
            $person->biography = $this->getPersonBiography($html);
        }

        return $person;
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
        if (preg_match('#<title>(.+) (- film [0-9]+ )?- AlloCiné</title>#U', $card, $tmp_title)) {
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
        if (preg_match('#<div class="ovw-synopsis-txt" itemprop="description">(.+)</div>#U', $card, $tmp_synopsis)) {
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
        if (preg_match('#<img class="thumbnail-img" src="(.+)" alt=".+" width="(215|216)" height="(290|288)" itemprop="image"\s*/>#U', $card, $tmp_img)) {
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
        if (preg_match('#<title>(.+) - film ([0-9]+) - AlloCiné</title>#U', $card, $tmp_release_date)) {
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
        if (preg_match('#\((([0-9]+h )?([0-9]+min)?)#', $card, $tmp_duration)) {
            $tmp_duration = $tmp_duration[1];
            $duration = 0;
            if (preg_match('#([0-9]+)h#', $tmp_duration, $duration_hours)) {
                $duration += $duration_hours[1] * 60;
            }
            if (preg_match('#([0-9]+)min#', $tmp_duration, $duration_minutes)) {
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
        preg_match_all('#<span itemprop="director" itemscope itemtype="http://schema.org/Person">\s+<a class="blue-link" href="/personne/fichepersonne_gen_cpersonne=([0-9]+).html" title=".+" itemprop="url"><span itemprop="name">(.+)</span></a>#U', $card, $tmp_creators);
        foreach ($tmp_creators[0] as $creator) {
            preg_match('#<span itemprop="director" itemscope itemtype="http://schema.org/Person">\s+<a class="blue-link" href="/personne/fichepersonne_gen_cpersonne=([0-9]+).html" title=".+" itemprop="url"><span itemprop="name">(.+)</span></a>#U', $creator, $tmp_creator);
            $std_creator = new \StdClass();
            $std_creator->id = trim($tmp_creator[1]);
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
        preg_match_all('#<strong class="meta-title" itemprop="actor" itemscope itemtype="http://schema.org/Person">\s+<a class="meta-title-link" href="/personne/fichepersonne_gen_cpersonne=([0-9]+).html">\s+<span itemprop="name">(.+)</span>\s+</a>#U', $card, $tmp_actors);

        foreach ($tmp_actors[0] as $actor) {
            preg_match('#<strong class="meta-title" itemprop="actor" itemscope itemtype="http://schema.org/Person">\s+<a class="meta-title-link" href="/personne/fichepersonne_gen_cpersonne=([0-9]+).html">\s+<span itemprop="name">(.+)</span>\s+</a>#U', $actor, $tmp_actor);
            $std_creator = new \StdClass();
            $std_creator->id = trim($tmp_actor[1]);
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
        preg_match_all('#<span itemprop="genre">(.+)</span>#U', $card, $film_types);
        foreach ($film_types[0] as $type) {
            preg_match('#<span itemprop="genre">(.+)</span>#U', $type, $tmp_type);
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
        if (preg_match('#<div class="meta-body-item">\s+<span class="light">Nationalités?</span>\s+(.+)\s+</div>#sU', $card, $film_nationalities)) {
            preg_match_all('#<span class=".+blue-link">(.+)</span>#U', $film_nationalities[1], $film_nationalities);
            foreach ($film_nationalities[1] as $nationality) {
                $nationalities[] = trim($nationality);
            }
        }

        return $nationalities;
    }

    /**
     * Get person nationality
     *
     * @param string $card The html of allocine page
     *
     * @return null|string
     */
    private function getPersonNationality($card)
    {
        $nationality = null;
        if (preg_match('#<span class="star_info lighten fl">Nationalité</span><div class="oflow_a">\s+(.+)\s+</div>#U', $card, $tmp_nationality)) {
            $nationality = trim($tmp_nationality[1]);
        }

        return $nationality;
    }

    /**
     * Get person birthdate
     *
     * @param string $card The html of allocine page
     *
     * @return \DateTime|null
     */
    private function getPersonBirthdate($card)
    {
        $birthdate = null;
        if (preg_match('#content="([0-9]{4}-[0-9]{2}-[0-9]{2})" itemprop="birthDate"#U', $card, $tmp_birthdate)) {
            $birthdate = new \DateTime($tmp_birthdate[1]);
        }

        return $birthdate;
    }

    /**
     * Get person image
     *
     * @param string $card The html of allocine page
     *
     * @return null|string
     */
    private function getPersonImg($card)
    {
        $img = null;
        if (preg_match('#<img itemprop="image" src=\'(.+)\' alt=\'.+\'/>#U', $card, $tmp_img)) {
            $img = trim($tmp_img[1]);
        }

        return $img;
    }

    /**
     * Get person biography
     *
     * @param string $card The html of allocine page
     *
     * @return null|string
     */
    private function getPersonBiography($card)
    {
        $biography = null;
        if (preg_match('#<div class="margin_20b">\s+(.+)\s+(<span class|</div>)#U', $card, $tmp_biography)) {
            $biography = trim($tmp_biography[1]);
            if (preg_match('#^(.+\.)[^\.]+\.\.\.$#', $biography, $tmp)) {
                $biography = $tmp[1];
            }
        }

        return $biography;
    }

    /**
     * Make request to allocine.fr
     * Sleep 1 seconds before return to ensure no ban and no ddos the site
     *
     * @param int    $allocine_id
     * @param string $type
     *
     * @throws \Exception
     *
     * @return mixed
     */
    private function makeRequest($allocine_id, $type = 'film')
    {
        switch ($type) {
            case 'film':
                $url = 'http://www.allocine.fr/film/fichefilm_gen_cfilm='.$allocine_id.'.html';
                break;
            case 'person':
                $url = 'http://www.allocine.fr/personne/fichepersonne_gen_cpersonne='.$allocine_id.'.html';
                break;
            default:
                throw new \Exception('Incorrect type. Supported type: film, person');
        }

        $headers = array(
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            "Accept-Encoding" => "gzip, deflate, sdch",
            "Accept-Language" => "fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4",
            "Cache-Control" => "no-cache",
            "Connection" => "keep-alive",
            "Host" => "www.allocine.fr",
            "Pragma" => "no-cache",
            "Upgrade-Insecure-Requests" => 1,
            "User-Agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36",
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, $headers);
        $response = curl_exec($curl);
        curl_close($curl);
        unset($curl);

        sleep(1);

        return $response;
    }
}