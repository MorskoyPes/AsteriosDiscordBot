<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Predis\Client;
use DateTime;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;



class CrawDomElements
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }
    /**
     * @Route("/craw")
     */
    public function index(Request $request)
    {
        try {
            $redis = new Client('tcp://127.0.0.1:6379');
        }
        catch (\Exception $e) {
            throw new \Exception("Connection Redis is lost". $e->getMessage());
        }

        $response = $this->client->request(
            'GET',
//            'https://asterios.tm/index.php?cmd=rss&serv=0&filter=keyboss'
            'https://asterios.tm/index.php?cmd=bd#mob.drop.25126'
        );

        $content = $response->getContent();
        $crawler = new Crawler($content);
        var_dump(11111111111);


//        $discord = new Discord([
//            'token' => 'ODUyNTc1NjEyMDg1NjAwMjU2.YMI06g.TyfLr_kYrI3Nlp-M-SwuGLztnmQ',
//        ]);

//        $discord->on('ready', function ($discord) {
//            echo "Bot is ready!", PHP_EOL;
//        });
//        $message = new Message($discord);
//
//        $channel->sendMessage('Hello, world!', false, $embed)->done(function (Message $message) {
//            // ...
//        });

//        $discord->run();

        $format = 'Y-m-d H:i:s';
        $now = time();



        $bosses = [
            'Kernon' => DateTime::createFromFormat($format, rtrim(preg_replace('/[a-zА-Яа-я\']/iu',
                '', $crawler->selectLink('Kernon')->text()), ": ")),
            'Hallate' => DateTime::createFromFormat($format, rtrim(preg_replace('/[a-zА-Яа-я\']/iu',
                '', $crawler->selectLink('Hallate')->text()), ": ")),
            'Golkonda' => DateTime::createFromFormat($format, rtrim(preg_replace('/[a-zА-Яа-я\']/iu',
                '', $crawler->selectLink('Golkonda')->text()), ": ")),
            'Cabrio' => DateTime::createFromFormat($format, rtrim(preg_replace('/[a-zА-Яа-я\']/iu',
                '', $crawler->selectLink('Cabrio')->text()), ": ")),
        ];

        /*
         * Запись в базу, если она пустая
         */
        if ($redis->get('Kernon') === null){
            $redis->set('Kernon', $bosses['Kernon']->format($format));
        }
        elseif ($redis->get('Hallate') === null){
            $redis->set('Hallate', $bosses['Hallate']->format($format));
        }
        elseif ($redis->get('Golkonda') === null){
            $redis->set('Golkonda', $bosses['Golkonda']->format($format));
        }
        elseif ($redis->get('Cabrio') === null){
            $redis->set('Cabrio', $bosses['Cabrio']->format($format));
        }

        /*
         * Оповещение о респе боссов
         */
        if (strtotime($bosses['Kernon']->format($format)) > strtotime($redis->get('Kernon'))){
            var_dump('Kernon resp');
            $redis->set('Kernon', $bosses['Kernon']->format($format));
        }
        elseif (strtotime($bosses['Hallate']->format($format)) > strtotime($redis->get('Hallate'))){
            var_dump('Hallate resp');
            $redis->set('Hallate', $bosses['Hallate']->format($format));
        }
        elseif (strtotime($bosses['Golkonda']->format($format)) > strtotime($redis->get('Golkonda'))){
            var_dump('Golkonda resp');
            $redis->set('Golkonda', $bosses['Golkonda']->format($format));
        }
        elseif (strtotime($bosses['Cabrio']->format($format)) > strtotime($redis->get('Cabrio'))){
            var_dump('Cabrio resp');
            $redis->set('Cabrio', $bosses['Cabrio']->format($format));
        }

        /*
         * Оповещение о начале респа
         */

        $kernonStart = new DateTime($redis->get('Kernon'));
        $hallateStart = new DateTime($redis->get('Hallate'));
        $golkondaStart = new DateTime($redis->get('Golkonda'));
        $cabrioStart = new DateTime($redis->get('Cabrio'));

        if ($now >= strtotime($kernonStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($kernonStart->modify('-4 second')->format($format))){
            var_dump('Начался респ Kernon');
        }
        if ($now >= strtotime($hallateStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($hallateStart->modify('-4 second')->format($format))){
            var_dump('Начался респ Hallate');
        }
        if ($now >= strtotime($golkondaStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($golkondaStart->modify('-4 second')->format($format))){
            var_dump('Начался респ Golkonda');
        }
        if ($now >= strtotime($cabrioStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($cabrioStart->modify('-4 second')->format($format))){
            var_dump('Начался респ Cabrio');
        }

        /*
         * Оповещение за два часа до респа
         */

        $kernonTwo = new DateTime($redis->get('Kernon'));
        $hallateTwo = new DateTime($redis->get('Hallate'));
        $golkondaTwo = new DateTime($redis->get('Golkonda'));
        $cabrioTwo = new DateTime($redis->get('Cabrio'));

        if ($now >= strtotime($kernonTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($kernonTwo->modify('-4 second')->format($format))){
            var_dump('До респа Kernon меньше 2 часов');
        }
        if ($now >= strtotime($hallateTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($hallateTwo->modify('-4 second')->format($format))){
            var_dump('До респа Hallate меньше 2 часов');
        }
        if ($now >= strtotime($golkondaTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($golkondaTwo->modify('-4 second')->format($format))){
            var_dump('До респа Golkonda меньше 2 часов');
        }
        if ($now >= strtotime($cabrioTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($cabrioTwo->modify('-4 second')->format($format))){
            var_dump('До респа Cabrio меньше 2 часов');
        }

        /*
         * Оповещение за один час до респа
         */

        $kernonOne = new DateTime($redis->get('Kernon'));
        $hallateOne = new DateTime($redis->get('Hallate'));
        $golkondaOne = new DateTime($redis->get('Golkonda'));
        $cabrioOne = new DateTime($redis->get('Cabrio'));

        if ($now >= strtotime($kernonOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($kernonOne->modify('-4 second')->format($format))){
            var_dump('До респа Kernon меньше часа');
        }
        if ($now >= strtotime($hallateOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($hallateOne->modify('-4 second')->format($format))){
            var_dump('До респа Hallate меньше часа');
        }
        if ($now >= strtotime($golkondaOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($golkondaOne->modify('-4 second')->format($format))){
            var_dump('До респа Golkonda меньше часа');
        }
        if ($now >= strtotime($cabrioOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            $now <= strtotime($cabrioOne->modify('-4 second')->format($format))){
            var_dump('До респа Calibrio меньше часа');
        }

        echo '<pre>';
        var_dump('Kernon: ' . $redis->get('Kernon'));
        var_dump('Hallate: ' . $redis->get('Hallate'));
        var_dump('Golkonda: ' . $redis->get('Golkonda'));
        var_dump('Cabrio: ' . $redis->get('Cabrio'));
        var_dump($bosses);
        echo "</pre>";
        return new Response();
    }
}