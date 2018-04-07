<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace CocoJr\Allocine;

/**
 * Class AllocineService
 *
 * A spider for allocine.fr
 * Only for personal use
 *
 * Need curl extension. See http://php.net/manual/en/book.curl.php
 */
class AllocineSpider
{
    /** @var string The base url of allocine */
    const BASE_URL = 'http://www.allocine.fr';

    /** @var array The hash of empty image in allocine.fr: you can use it to check if is the default empty image with md5_file */
    const NO_IMG_MD5 = array(
        '5cddb87648002a3a63e20409504f026d',
        'caaaccdaceca7223f2acaec2a31fb7c9',
    );

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
    protected function makeRequest($allocine_id, $type = 'film')
    {
        switch ($type) {
            case 'film':
                $url = $this::BASE_URL.'/film/fichefilm_gen_cfilm='.$allocine_id.'.html';
                break;
            case 'person':
                $url = $this::BASE_URL.'/personne/fichepersonne_gen_cpersonne='.$allocine_id.'.html';
                break;
            case 'serie':
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