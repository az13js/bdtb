<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Spider extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'FORTUNE SPIDER';

    private $curl = null;
    private $curlError = '';

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
        $this->flushOnce();
    }

    public function flushOnce()
    {
        for ($page = 1; $page < 100; $page++) {
            $newArtital = 0;
            $data = $this->getList($page);
            if (false == $data) {
                break;
            }
            foreach ($data['docs'] as $unit) {
                $fileName = $this->getFileName($unit);
                if (file_exists('public/storage/' . $fileName)) {
                    continue;
                }
                $context = $this->getMarkdownText($unit['url']);
                try {
                    file_put_contents('public/storage/' . $fileName, $context);
                } catch (\Exception $e) {
                    file_put_contents('public/storage/' . hash('sha256', $fileName) . '.md' , $context);
                }
                $newArtital++;
            }
            if ($newArtital == 0) {
                Log::info('NO UPDATE, PAGE: '.$page.'.');
            } else {
                Log::info('UPDATE: ' . $newArtital . ', PAGE: ' . $page);
            }
        }
    }

    public function getFileName($unit)
    {
        if (is_array($unit)) {
            if (isset($unit['pubtime']) && isset($unit['title'])) {
                $dateInfo = explode(' ', $unit['pubtime']);
                if ('UTF-8' != mb_detect_encoding($unit['title'], mb_detect_order(), false)) {
                    $afterConvert = mb_convert_encoding($unit['title'], 'UTF-8', 'GBK, GB2312, ISO-8859-1, UTF-8');
                } else {
                    $afterConvert = $unit['title'];
                }
                return $dateInfo[0] . ' ' . $unit['title'] . '.md';
            }
        }
        return false;
    }

    public function getMarkdownText($url)
    {
        $context = $this->curlGet($url);
        if (empty($context)) {
            Log::error('[CURL]' . $this->curlError);
            $this->curlError = '';
            return '';
        }
        if ('UTF-8' != mb_detect_encoding($context, mb_detect_order(), false)) {
            $afterConvert = mb_convert_encoding($context, 'UTF-8', 'GBK, GB2312, ISO-8859-1, UTF-8');
        } else {
            $afterConvert = $context;
        }
        if (mb_stripos($afterConvert, '<title>')) {
            $titleStart = mb_substr($afterConvert, mb_stripos($afterConvert, '<title>') + 7, mb_strlen($afterConvert) - mb_stripos($afterConvert, '<title>') - 7);
            $title = mb_substr($titleStart, 0, mb_stripos($titleStart, '</title>'));
        } else {
            $title = 'unknow';
        }
        $m = $afterConvert;
        $dataset = [];
        while ($p = mb_stripos($m, '<p>')) {
            $left = mb_substr($m, 0, $p);
            $right = mb_substr($m, $p + 3, mb_strlen($m) - $p - 3);
            $left = trim(str_ireplace(PHP_EOL, '', str_ireplace('</p>', '', $left)));
            if (mb_substr_count($left, '<') >= 2) {
                $left = mb_substr($left, 0, mb_strpos($left, '<'));
            }
            if (!empty($left)) {
                $dataset[] = $left;
            }
            $m = $right;
        }
        unset($dataset[0]);
        $body = '# ' . $title . PHP_EOL;
        foreach ($dataset as $p) {
            $body .= PHP_EOL . $p . PHP_EOL;
        }
        return $body;
    }

    public function getList(int $pager = 1, int $pagenum = 8)
    {
        $result = $this->curlGet("http://channel.chinanews.com/cns/cjs/fortune.shtml?pager=$pager&pagenum=$pagenum&_=" . (1000 * time()));
        if (empty($this->curlError)) {
            $result = ltrim($result, 'specialcnsdata = ');
            $result = mb_substr($result, 0, 1 + mb_strrpos($result, '}'));
            $data = json_decode($result, true);
            if (is_null($data)) {
                Log::error('[JSON_DECODE]' . serialize($result));
            }
            return $data;
        }
        Log::error('[CURL]' . $this->curlError);
        $this->curlError = '';
        return false;
    }

    public function curlGet($url)
    {
        if (is_null($this->curl)) {
            $h = curl_init();
            curl_setopt($h, CURLOPT_AUTOREFERER, true);
            curl_setopt($h, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($h, CURLOPT_FORBID_REUSE, false);
            curl_setopt($h, CURLOPT_FRESH_CONNECT, false);
            curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($h, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($h, CURLOPT_DNS_CACHE_TIMEOUT, 60 * 5);
            curl_setopt($h, CURLOPT_MAXCONNECTS, 10);
            curl_setopt($h, CURLOPT_MAXREDIRS, 20);
            curl_setopt($h, CURLOPT_TIMEOUT, 60 * 2);
            curl_setopt($h, CURLOPT_REFERER, 'http://fortune.chinanews.com/');
            curl_setopt($h, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36');
            curl_setopt($h, CURLOPT_HTTPHEADER, ['Content-type: text/html; charset=UTF-8']);
            $this->curl = $h;
        } else {
            $h = $this->curl;
        }
        curl_setopt($h, CURLOPT_URL, $url);
        $data = curl_exec($h);
        if (false === $data) {
            $this->curlError = curl_error($h);
        }
        return $data;
    }
}