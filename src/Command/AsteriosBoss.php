<?php
namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Predis\Client;
use DateTime;

class AsteriosBoss extends Command
{
    protected static $defaultName = 'app:boss-respawn';

    public  $client;
//    /**
//     * @var LoggerInterface
//     */
//    private $logger;
//
//    /**
//     * @param LoggerInterface $logger
//     * @required
//     */
//    public function setLogger(LoggerInterface $logger)
//    {
//        $this->logger = $logger;
//    }

    /**
     * @param HttpClientInterface $client
     *
     * @required
     */
    public function setClient(HttpClientInterface $client): void
    {
        $this->client = $client;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $redis = new Client('tcp://127.0.0.1:6379');
        }
        catch (\Exception $e) {
            throw new \Exception("Connection Redis is lost". $e->getMessage());
        }

        $response = $this->client->request(
            'GET',
            'https://asterios.tm/index.php?cmd=rss&serv=0&filter=keyboss'
        );

        $content = $response->getContent();
        $crawler = new Crawler($content);

        $command = $this->getApplication()->find(SendMessage::getDefaultName());

        $format = 'Y-m-d H:i:s';
        $now = new DateTime('now');


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
        if ($redis->exists('Kernon')){
            $redis->set('Kernon', $bosses['Kernon']->format($format));
        }
        elseif ($redis->exists('Hallate')){
            $redis->set('Hallate', $bosses['Hallate']->format($format));
        }
        elseif ($redis->exists('Golkonda')){
            $redis->set('Golkonda', $bosses['Golkonda']->format($format));
        }
        elseif ($redis->exists('Cabrio')){
            $redis->set('Cabrio', $bosses['Cabrio']->format($format));
        }

        /*
         * Оповещение о респе боссов
         */
        if (strtotime($bosses['Kernon']->format($format)) > strtotime($redis->get('Kernon'))){
            $message = [
                'title' => 'Босс Kernon был убит',
                'description' => $bosses['Kernon']->format($format),
                'field_name' => 'Начало респауна:',
                'field_value' => $bosses['Kernon']->modify('+18 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25054.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $redis->set('Kernon', $bosses['Kernon']->modify('-18 hours')->format($format));
        }
        if (strtotime($bosses['Hallate']->format($format)) > strtotime($redis->get('Hallate'))){
            $message = [
                'title' => 'Босс Hallate был убит',
                'description' => $bosses['Hallate']->format($format),
                'field_name' => 'Начало респауна:',
                'field_value' => $bosses['Hallate']->modify('+18 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25220.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $redis->set('Hallate', $bosses['Hallate']->modify('-18 hours')->format($format));
        }
        if (strtotime($bosses['Golkonda']->format($format)) > strtotime($redis->get('Golkonda'))){
            $message = [
                'title' => 'Босс Golkonda был убит',
                'description' => $bosses['Golkonda']->format($format),
                'field_name' => 'Начало респауна:',
                'field_value' => $bosses['Golkonda']->modify('+18 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25126.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $redis->set('Golkonda', $bosses['Golkonda']->modify('-18 hours')->format($format));
        }
        if (strtotime($bosses['Cabrio']->format($format)) > strtotime($redis->get('Cabrio'))){
            $message = [
                'title' => 'Босс Cabrio был убит',
                'description' => $bosses['Cabrio']->format($format),
                'field_name' => 'Начало респауна:',
                'field_value' => $bosses['Cabrio']->modify('+18 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25035.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $redis->set('Cabrio', $bosses['Cabrio']->modify('-18 hours')->format($format));
        }

        /*
         * Оповещение о начале респа
         */
        $kernonStart = new DateTime($redis->get('Kernon'));
        $hallateStart = new DateTime($redis->get('Hallate'));
        $golkondaStart = new DateTime($redis->get('Golkonda'));
        $cabrioStart = new DateTime($redis->get('Cabrio'));

        if (strtotime($now->format($format)) >=
            strtotime($kernonStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($kernonStart->modify('-4 second')->format($format))){
            $message = [
                'title' => 'Начался респун Kernon',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+12 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25054.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-12 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($hallateStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($hallateStart->modify('-4 second')->format($format))){
            $message = [
                'title' => 'Начался респун Hallate',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+12 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25220.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-12 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($golkondaStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($golkondaStart->modify('-4 second')->format($format))){
            $message = [
                'title' => 'Начался респун Golkonda',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+12 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25126.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-12 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($cabrioStart->modify('+18 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($cabrioStart->modify('-4 second')->format($format))){
            $message = [
                'title' => 'Начался респун Cabrio',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+12 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25035.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-12 hours')->format($format);
        }

        /*
         * Оповещение за два часа до респа
         */
        $kernonTwo = new DateTime($redis->get('Kernon'));
        $hallateTwo = new DateTime($redis->get('Hallate'));
        $golkondaTwo = new DateTime($redis->get('Golkonda'));
        $cabrioTwo = new DateTime($redis->get('Cabrio'));

        if (strtotime($now->format($format)) >=
            strtotime($kernonTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($kernonTwo->modify('-4 second')->format($format))){
            $message = [
                'title' => 'До респа Kernon меньше 2 часов',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+2 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25054.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-2 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($hallateTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($hallateTwo->modify('-4 second')->format($format))){
            $message = [
                'title' => 'До респа Hallate меньше 2 часов',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+2 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25220.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-2 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($golkondaTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($golkondaTwo->modify('-4 second')->format($format))){
            $message = [
                'title' => 'До респа Golkonda меньше 2 часов',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+2 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25126.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-2 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($cabrioTwo->modify('+28 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($cabrioTwo->modify('-4 second')->format($format))){
            $message = [
                'title' => 'До респа Cabrio меньше 2 часов',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+2 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25035.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-2 hours')->format($format);
        }

        /*
         * Оповещение за один час до респа
         */
        $kernonOne = new DateTime($redis->get('Kernon'));
        $hallateOne = new DateTime($redis->get('Hallate'));
        $golkondaOne = new DateTime($redis->get('Golkonda'));
        $cabrioOne = new DateTime($redis->get('Cabrio'));

        if (strtotime($now->format($format)) >=
            strtotime($kernonOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($kernonOne->modify('-4 second')->format($format))){
            $message = [
                'title' => 'СПЕШИТЕ! До респа Kernon меньше часа',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+1 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25054.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-1 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($hallateOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($hallateOne->modify('-4 second')->format($format))){
            $message = [
                'title' => 'СПЕШИТЕ! До респа Hallate меньше часа',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+1 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25220.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-1 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($golkondaOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($golkondaOne->modify('-4 second')->format($format))){
            $message = [
                'title' => 'СПЕШИТЕ! До респа Golkonda меньше часа',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+1 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25126.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-1 hours')->format($format);
        }
        if (strtotime($now->format($format)) >=
            strtotime($cabrioOne->modify('+29 hour')->modify('+3 second')->format($format)) &&
            strtotime($now->format($format)) <=
            strtotime($cabrioOne->modify('-4 second')->format($format))){
            $message = [
                'title' => 'СПЕШИТЕ! До респа Calibrio меньше часа',
                'description' => $now,
                'field_name' => 'Респаун будет до:',
                'field_value' => $now->modify('+1 hours')->format($format),
                'image' => 'https://asterios.tm/design/img/mob/25035.jpg',
            ];
            $command->run(new ArrayInput(['message' => $message]), new NullOutput());
            $now->modify('-1 hours')->format($format);
        }

        return 0;
    }
}