<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\News;

class ParserRbkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:rbk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parsing news from rbk.ru';

    private $parse_url = 'https://www.rbc.ru/';

    private $limit_news = 15;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $html = $this->getContent($this->parse_url);

        $crawler = new Crawler(null, $this->parse_url);
        $crawler->addHtmlContent($html, 'UTF-8');

        $news_block = $crawler->filter(".js-news-feed-list .news-feed__item");
        foreach ($news_block as $item) {

            try {
                $link = $this->getNewsLink($item);
                $title = $this->getNewsTitle($item);
                $time = $this->getNewsTime($item);
                $id = $this->getNewsId($item);
            } catch (\Exception $e) {
                echo "News block parsing error " . parse_url($link)['host'] ? parse_url($link)['host'] : null;
                echo "\n" . $e->getMessage();
                echo "\n" . $e->getTraceAsString();
                continue;
            }

            $link_host = parse_url($link)['host'] ? parse_url($link)['host'] : null;
            $news[] = [
                'title' => trim($title),
                'link' => $link,
                'id' => $id,
                'partners_news' => $link_host,
                'news_time' => Carbon::createFromTimestamp($time)->setTimezone('Europe/Moscow')->toDateTimeString(),
            ];
        }

        $news_ids = array_column($news, 'id');
        $db_news = News::whereIn('external_id', $news_ids)
            ->pluck('external_id')
            ->all();

        $batch = [];
        $count_news = 0;

        foreach ($news as $i => $item) {
            if (in_array($item['id'], $db_news))
                continue;

            $count_news++;
            $batch[$i] = [
                'external_id' => $item['id'],
                'title' => $item['title'],
                'img' => null,
                'text' => null,
                'original_link' => $item['link'],
                'partners_news' => $item['partners_news'],
                'news_time' => $item['news_time'],
            ];

            try {
                $html_item = $this->getContent($item['link']);
                $crawler_item = new Crawler(null, $item['link']);
                $crawler_item->addHtmlContent($html_item, 'UTF-8');
                $news_body = $crawler_item->filter('[data-id="' . $item['id'] . '"]');

                if ($news_body && count($image_box = $news_body->filter('.article__main-image__image')))
                    $batch[$i]['img'] = $image_box->attr('src') ? $image_box->attr('src') : null;

                $batch[$i]['text'] = $this->getNewsText($news_body);

            } catch (\Exception $e) {
                echo "Ошибка парсинга полной новости: " . $item['link'];
                echo "\n" . $e->getMessage();
                echo "\n" . $e->getTraceAsString();
                continue;
            }

            if ($count_news >= $this->limit_news)
                break;
        }

        News::insert($batch);

        dd(count($batch) . ' новых новости(-ей)');
    }

    private function getContent($url)
    {
        $curl = curl_init($url);
        $options = array(
            CURLOPT_HTTPHEADER => [
                'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36',
            ],
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FRESH_CONNECT => 0,
            CURLOPT_RETURNTRANSFER => 1,

        );
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        $content = curl_getinfo($curl);
        $content['errno'] = curl_errno($curl);
        $content['error'] = curl_error($curl);
        $content['result'] = $result;
        curl_close($curl);

        if ($content['errno'])
            dd(["ERROR get data from url", $url, $content['error']]);

        return $content['result'];
    }

    private function getNewsLink($news_body)
    {
        return $news_body->getAttribute('href');
    }

    private function getNewsTitle($news_body)
    {
        return trim(preg_replace("/\s\s+/", " ", $news_body->textContent));
    }

    private function getNewsTime($news_body)
    {
        return $news_body->getAttribute('data-modif');
    }

    private function getNewsId($news_body)
    {
        return explode('id_newsfeed_', $news_body->getAttribute('id'))[1];
    }

    private function getNewsText($news_body)
    {
        $text = '';
        foreach ($news_body->filter('p') as $p)
            $text .= $p->textContent;

        return $text;
    }
}
