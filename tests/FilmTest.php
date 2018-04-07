<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace CocoJr\Allocine\Tests;

use CocoJr\Allocine\FilmSpider;
use PHPUnit\Framework\TestCase;

/**
 * Class FilmTest
 */
class FilmTest extends TestCase
{
    /**
     * @dataProvider fetchFilmProvider
     */
    public function testFetchFilm($allocine_id, $fullPerson = false, $callback = null)
    {
        $spider = new FilmSpider();

        $film = $spider->fetchFilm($allocine_id, $fullPerson);

        if ($callback) {
            $this->$callback($film, $fullPerson);
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
            [1523, false, 'checkFilm'],
            [1523, true, 'checkFilm'],
            [59809, false, 'checkFilm'],
            [59809, true, 'checkFilm'],
        ];
    }

    /**
     * Check film
     *
     * @param object $film
     * @param bool   $full
     */
    private function checkFilm($film, $full)
    {
        $this->checkFilmProperty($film);

        foreach ($film->creators as $creator) {
            $this->checkPerson($creator, $full);
        }

        foreach ($film->actors as $actor) {
            $this->checkPerson($actor, $full);
        }

        if (method_exists($this, 'checkFilm'.$film->allocineId)) {
            $this->{'checkFilm'.$film->allocineId}($film);
        }
    }

    /**
     * Check film property
     *
     * @param object $film
     */
    private function checkFilmProperty($film)
    {
        $this->assertTrue(property_exists($film, 'allocineId'));
        $this->assertTrue(property_exists($film, 'title'));
        $this->assertTrue(property_exists($film, 'synopsis'));
        $this->assertTrue(property_exists($film, 'img'));
        $this->assertTrue(property_exists($film, 'releaseDate'));
        $this->assertTrue(property_exists($film, 'duration'));
        $this->assertTrue(property_exists($film, 'creators'));
        $this->assertTrue(property_exists($film, 'actors'));
        $this->assertTrue(property_exists($film, 'types'));
        $this->assertTrue(property_exists($film, 'nationalities'));
    }

    /**
     * Check not found
     *
     * @param object $film
     */
    private function checkNotFound($film)
    {
        $this->assertFalse($film);
    }

    /**
     * Check person
     *
     * @param object $person
     * @param bool   $full
     */
    private function checkPerson($person, $full)
    {
        $this->assertTrue(property_exists($person, 'allocineId'));
        $this->assertTrue(property_exists($person, 'name'));

        if ($full) {
            $this->checkFullPerson($person);
        }

        if (method_exists($this, 'checkPerson'.$person->allocineId)) {
            $this->{'checkPerson'.$person->allocineId}($person, $full);
        }
    }

    /**
     * Check full person property
     *
     * @param object $person
     */
    private function checkFullPerson($person)
    {
        $this->assertTrue(property_exists($person, 'nationality'));
        $this->assertTrue(property_exists($person, 'birthDate'));
        $this->assertTrue(property_exists($person, 'img'));
        $this->assertTrue(property_exists($person, 'biography'));
    }

    /**
     * Check film: http://www.allocine.fr/film/fichefilm_gen_cfilm=1523.html
     *
     * @param object $film
     */
    private function checkFilm1523($film)
    {
        $this->assertEquals(1523, $film->allocineId);
        $this->assertEquals('Les Enfants de la guerre', $film->title);
        $this->assertEquals(null, $film->synopsis);
        $this->assertEquals('http://fr.web.img2.acsta.net/c_215_290/commons/v9/common/empty/empty.png', $film->img);
        $this->assertEquals(new \DateTime('1976-01-01'), $film->releaseDate);
        $this->assertEquals(NULL, $film->duration);
        $this->assertEquals(1, count($film->creators));
        $this->assertEquals(array(), $film->actors);
        $this->assertEquals(array('Documentaire'), $film->types);
        $this->assertEquals(array(), $film->nationalities);
    }

    /**
     * Check film: http://www.allocine.fr/film/fichefilm_gen_cfilm=59809.html
     *
     * @param string $film
     */
    private function checkFilm59809($film)
    {
        $synopsis = 'Jason Bourne a longtemps été un homme sans patrie, sans passé ni mémoire. Un conditionnement physique et mental d\'une extrême brutalité en avait fait une machine à tuer - l\'exécuteur le plus implacable de l\'histoire de la CIA. L\'expérience tourna court et l\'Agence décida de le sacrifier.<br>Laissé pour mort, Jason se réfugie en Italie et entreprend une lente et périlleuse remontée dans le temps à la recherche de son identité. Après l\'assassinat de sa compagne, Marie, il retrouve l\'instigateur du programme Treadstone qui a fait de lui un assassin et l\'a condamné à l\'errance. S\'estimant vengé par la mort de ce dernier, il n\'aspire plus qu\'à disparaître et vivre en paix. Tout semble rentré dans l\'ordre : Treadstone ne serait plus qu\'une page noire – une de plus - dans l\'histoire de l\'Agence...<br>Mais le Département de la Défense lance en grand secret un second programme encore plus sophistiqué : Blackbriar, visant à fabriquer une nouvelle génération de tueurs supérieurement entraînés. Jason est, pour le directeur des opérations spéciales, une menace et une tache à effacer au plus vite. Ordre est donné de le supprimer. La traque recommence, de Moscou à Paris, de Madrid à Londres et Tanger...';

        $this->assertEquals(59809, $film->allocineId);
        $this->assertEquals('La Vengeance dans la peau', $film->title);
        $this->assertEquals($synopsis, $film->synopsis);
        $this->assertEquals("http://fr.web.img6.acsta.net/c_215_290/medias/nmedia/18/63/34/23/18794863.jpg", $film->img);
        $this->assertEquals(new \DateTime('2007-01-01'), $film->releaseDate);
        $this->assertEquals(116, $film->duration);
        $this->assertEquals(1, count($film->creators));
        $this->assertEquals(4, count($film->actors));
        $this->assertEquals(array('Action', 'Aventure', 'Espionnage', 'Thriller'), $film->types);
        $this->assertEquals(array('Américain', 'Allemand'), $film->nationalities);
    }

    /**
     * Check person: http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=4226.html
     *
     * @param object $person
     * @param bool   $full
     */
    private function checkPerson4226($person, $full)
    {
        $this->assertEquals(4226, $person->allocineId);
        $this->assertEquals('Jocelyne Saab', $person->name);
        if ($full) {
            $this->assertEquals(null, $person->nationality);
            $this->assertEquals(null, $person->birthDate);
            $this->assertEquals(null, $person->img);
            $this->assertEquals(null, $person->biography);
        }
    }

    /**
     * Check person: http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=1192.html
     *
     * @param object $person
     * @param bool   $full
     */
    private function checkPerson1192($person, $full)
    {
        $this->assertEquals(1192, $person->allocineId);
        if ($full) {
            $biography = 'Après des études littéraires à la prestigieuse université de Harvard, Matt Damon choisit de monter sur les planches, et connaît son premier succès avec la pièce The Speed of Darkness de Steve Tesich. Il fait ensuite des débuts remarqués au cinéma en 1988 dans la comédie dramatique Mystic Pizza de Donald Petrie aux côtés de Julia Roberts, star montante à l\'époque, et de Lili Taylor. Le jeune acteur décroche ensuite plusieurs seconds rôles, notamment dans le Geronimo de Walter Hill en 1993, puis donne la réplique en 1996 à Meg Ryan et Denzel Washington dans A l\'épreuve du feu de Edward Zwick. Désormais considéré comme une valeur montante à Hollywood, il campe L\'Idealiste de Francis Ford Coppola en 1997. La même année, il écrit avec son ami Ben Affleck le scénario de Will Hunting. Ce film, réalisé par Gus Van Sant, où il tient également le rôle principal, est salué par la critique et le pub...';

            $this->assertEquals('Matthew Paige Damon', $person->name);
            $this->assertEquals('Américain', $person->nationality);
            $this->assertEquals(new \DateTime('1970-10-08'), $person->birthDate);
            $this->assertEquals('http://fr.web.img2.acsta.net/c_215_290/pictures/16/07/13/11/16/193188.jpg', $person->img);
            $this->assertEquals($biography, $person->biography);
        } else {
            $this->assertEquals('Matt Damon', $person->name);
        }
    }

    /**
     * Check person: http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=33200.html
     *
     * @param object $person
     * @param bool   $full
     */
    private function checkPerson33200($person, $full)
    {
        $this->assertEquals(33200, $person->allocineId);
        if ($full) {
            $biography = 'Julia Stiles débute à 11 ans sur les planches des célèbres théâtres La Mama Theatre et Kitchen Theatre de New York, avant de faire ses premières apparitions sur grand écran dans I love you, I love you not (1996), film qui révéla également Claire Danes, James Van Der Beek et Jude Law, et dans Ennemis rapprochés  où elle campe la fille d\'Harrison Ford. A l\'âge de 17 ans, elle tient seule l\'affiche du thriller indépendant Wicked, petit film avec lequel elle fait sensation au festival de Sundance. C\'est en 1999 que la jeune femme rencontre le succès grâce à la comédie pour adolescents 10 Bonnes raisons de te larguer, où elle incarne la représentation moderne de La Mégère apprivoisée de William Shakespeare dans une faculté américaine.Rendue célèbre grâce à ce film, la jeune actrice surfe sur la vague du succès en continuant à se produire dans des remakes actualisés de pièces du dramaturge ang...';

            $this->assertEquals('Julia O\'Hara Stiles', $person->name);
            $this->assertEquals('Américaine', $person->nationality);
            $this->assertEquals(new \DateTime('1981-03-28'), $person->birthDate);
            $this->assertEquals('http://fr.web.img6.acsta.net/c_215_290/pictures/17/06/14/11/21/321059.jpg', $person->img);
            $this->assertEquals($biography, $person->biography);
        } else {
            $this->assertEquals('Julia Stiles', $person->name);
        }
    }

    /**
     * Check person: http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=15239.html
     *
     * @param object $person
     * @param bool   $full
     */
    private function checkPerson15239($person, $full)
    {
        $this->assertEquals(15239, $person->allocineId);
        if ($full) {
            $biography = 'D\'origines écossaises et hawaïennes, David Strathairn suit des cours de comédie au Williams College, où il a notamment pour camarade le réalisateur John Sayles, qui fera souvent appel à lui dans ses films. Une fois son diplôme en poche, Strathairn s\'engage en Floride dans une troupe de cirque ambulante, avec laquelle il part six mois sur les routes. Arrivé à New York, il passe plusieurs années à jouer au théâtre, partant souvent faire des tournées estivales à travers le pays.C\'est donc John Sayles qui le fait démarrer en 1980, dans son premier long métrage Return of the secaucus seven. Il lui offrira ensuite plusieurs rôles, notamment dans Matewan (1987), Eight Men Out (1988), City of Hope (1991) ou encore Passion Fish. C\'est dans ce dernier film, sorti en 1992, que Strathairn est révélé à la critique et au public, d\'autant plus qu\'il apparaît la même année dans Une équipe hors du commun...';

            $this->assertEquals('David Russell Strathairn', $person->name);
            $this->assertEquals('Américain', $person->nationality);
            $this->assertEquals(new \DateTime('1949-01-26'), $person->birthDate);
            $this->assertEquals('http://fr.web.img5.acsta.net/c_215_290/pictures/16/10/24/09/38/025751.jpg', $person->img);
            $this->assertEquals($biography, $person->biography);
        } else {
            $this->assertEquals('David Strathairn', $person->name);
        }
    }
}
