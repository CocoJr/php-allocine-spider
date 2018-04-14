<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace CocoJr\Allocine;

/**
 * Class PersonSpider
 */
class PersonSpider extends AllocineSpider
{
    const REGEX = array(
        'card' => '#<main id="content-layout" class="content-layout cf entity">(.+)</main>#isU',
        'name' => '#<div class="meta-body-item">\s+<span class=".+">Nom de naissance </span>\s+<h2 class="item">(.+)</h2>\s+</div>#U',
        'nationality' => '#<div class="meta-body-item">\s+<span class=".+">Nationalité </span>\s+(.+)\s+</div>#U',
        'birthDate' => '#<span class=".+">Naissance </span>.+<strong>(.+)</strong>#isU',
        'img' => '#<img class="thumbnail-img" src="(.+)" alt="" width="215" height="290" />#U',
        'biography' => '#(<div class="person-biography">|Biographie</span></h2></div>\s+<div class="content-txt">)(.+)</div>#sU',
    );

    /**
     * Fetch person from allocine.fr
     *
     * @param int $allocine_id The ID of person in Allocine.fr
     *
     * @throws \Exception
     *
     * @return bool|\StdClass
     */
    public function fetchPerson($allocine_id)
    {
        $person = false;
        $html = $this->makeRequest($allocine_id, 'person');

        if (preg_match($this::REGEX['card'], $html, $card)) {
            $card = $card[1];

            if ($name = $this->getPersonName($card)) {
                $person = new \StdClass;
                $person->allocineId = $allocine_id;
                $person->name = html_entity_decode($name, ENT_QUOTES);
                $person->birthDate = $this->getPersonBirthdate($card);
                $person->img = $this->getPersonImg($html);
                $person->nationality = html_entity_decode($this->getPersonNationality($card), ENT_QUOTES);
                $person->biography = html_entity_decode($this->getPersonBiography($html), ENT_QUOTES);
            }
        }

        return $person;
    }

    protected function getPersonName($card)
    {
        if (preg_match($this::REGEX['name'], $card, $tmp)) {
            return trim($tmp[1]);
        }

        return false;
    }

    /**
     * Get person nationality
     *
     * @param string $card The html of allocine page
     *
     * @return null|string
     */
    protected function getPersonNationality($card)
    {
        $nationality = null;
        if (preg_match($this::REGEX['nationality'], $card, $tmp_nationality)) {
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
    protected function getPersonBirthdate($card)
    {
        $birthdate = null;
        if (preg_match($this::REGEX['birthDate'], $card, $tmp_birthdate)) {
            $month = array(
                'janvier' => '01',
                'février' => '02',
                'mars' => '03',
                'avril' => '04',
                'mai' => '05',
                'juin' => '06',
                'juillet' => '07',
                'août' => '08',
                'septembre' => '09',
                'octobre' => '10',
                'novembre' => '11',
                'décembre' => '12',
            );
            $tmp_birthdate = explode(' ', trim($tmp_birthdate[1]));
            $tmp_birthdate[1] = $month[$tmp_birthdate[1]];
            $birthdate = new \DateTime($tmp_birthdate[2].'-'.$tmp_birthdate[1].'-'.$tmp_birthdate[0]);
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
    protected function getPersonImg($card)
    {
        $img = null;
        if (preg_match($this::REGEX['img'], $card, $tmp_img)) {
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
    protected function getPersonBiography($card)
    {
        $biography = null;
        if (preg_match($this::REGEX['biography'], $card, $tmp_biography)) {
            $biography = trim(preg_replace('#<span class=".+">Lire (la suite|plus)</span>#iU', '', $tmp_biography[2]));
        }

        return $biography;
    }
}