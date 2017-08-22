<?php
namespace tizis\MangaParser\Information;

use DiDom\Document;
use Curl\MultiCurl;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Класс получения информации о чатстях
 */

class MangaInformationChapterController extends MangaOwnerController
{
  private $items; // части
  private $url; // ссылка на мангу
  private $id; // ид манги
  private $chaptersCountInformationGet; // кол-во частей парсить
  private $chaptersDownloadImg; // парсить ли картинки
  public $isProxy; // использовать ли проекси

  function __construct($items, $id, $url, $count, $getImg, $isProxy)
  {
    parent::__construct();
    $this->id = $id;
    $this->url = $url;
    $this->isProxy = $isProxy; //
    $this->items = array_reverse($items);
    $this->chaptersCountInformationGet = $count;
    $this->chaptersDownloadImg = $getImg;
  }

  /**
   * Функция получения информации о чатстях
   * @return array
   */
  public function getMangaChapters()
  {
    $response = $this->getChapterContentMulti($this->items);
    if ($this->chaptersDownloadImg == true) {
      $this->getImgMulti($response);
    }
    return $response;
  }

  /**
   * Функция парсинга информаии о частях
   *
   * @param $items array
   *
   * @return array
   */
  private function getChapterContentMulti($items)
  {
    $response = array();
    $multi_curl = new MultiCurl();
    $multi_curl->setConcurrency(10);
    $multi_curl->setTimeout(160);
    $multi_curl->success(function($instance) use(&$items, &$response) {
      //  echo 'call to "' . $instance->url . '" was successful.' . "<br>\n";
        $key = array_search(parse_url($instance->url, PHP_URL_PATH), array_column($items, 'url'));

        if ($key !== false) {
          $element = $items[$key];
          $response[$key] = $this->getChapterContent($element, $instance->response, $key);
        }else {
          var_dump($instance->url);
          var_dump($key);
          var_dump(parse_url($instance->url, PHP_URL_PATH));
          var_dump($items);
          echo 'parse "' . $instance->url . '" was unsuccessful.' . "<br>\n";
        }
    });
    $multi_curl->error(function($instance) {
      echo 'call to "' . $instance->url . '" was unsuccessful.' . "<br>\n";
      echo 'error code: ' . $instance->errorCode . "\n";
      echo 'error message: ' . $instance->errorMessage . "\n";
      if ($instance->errorCode == 7) { // хуевое проекси
        # code...
      }
    });

    $proxyList = json_decode($this->GetContent('mp.me/proxy.json'), true);

    foreach ($items as $key => $item) {
      if ($this->chaptersCountInformationGet !== true
          && (is_numeric($this->chaptersCountInformationGet)
          && $key == $this->chaptersCountInformationGet)
      ) {
        break; // обрабатываем только первую главу, чтобы не заблокировали доступ за ддос
      }
      $url = "http://".parse_url($this->url, PHP_URL_HOST).$item['url'].$this->mature;
      if ($this->isProxy == true && !empty($proxyList)) {
        $proxy = $proxyList[array_rand($proxyList)];
        $multi_curl->setOpt(CURLOPT_HTTPPROXYTUNNEL, 1);
        var_dump("{$proxy['ip']}:{$proxy['port']}");
        $multi_curl->setOpt(CURLOPT_PROXY, "{$proxy['ip']}:{$proxy['port']}");
      }
      $multi_curl->addGet($url);
    }
    $multi_curl->start();
    return $response;
  }

  /**
   * Функция разбора хтмл данных части манги
   *
   * @param array $chapter
   * @param Document object $html
   * @param int $id
   *
   * @return array
   */
  private function getChapterContent($chapter, $html, $id)
  {
    $html = new Document($html);
    $script = $html->find('script')[10];
    foreach ($html->find('script') as $key => $value) {
       if (mb_strpos($value->text(), 'servers')) {
         $script = $value->text();
         break;
       }
    }

    $response['id'] = $id; // ид главы, не тоже самое что num: ид это индентификатор в глобальном смысле, num - очередность.
    $response['url']['path'] = $chapter['url'];
    $response['url']['full'] = "http://".parse_url($this->url, PHP_URL_HOST).$chapter['url'];;
    $response['extra'] = $chapter['extra'];
    $response['name'] = preg_replace('/<a\b[^>]*>(.*?)<\/a>/i', '', $html->first('h1')->innerHtml());

    if ((mb_strpos($response['name'], 'Экстра')
        || mb_strpos($html->first('h1')->innerHtml(), 'экстра'))
        && !mb_strpos($response['name'], ' - ')
    ) {

      if (mb_strpos($response['name'], 'Экстра')) $extra = 'Экстра';
      if (mb_strpos($response['name'], 'экстра')) $extra = 'экстра';
      if (isset($extra)) {
          $response['name'] = explode($extra, $response['name']);
          $response['num'] = trim($response['name'][0]);
          $response['name'] = trim($response['name'][1]);
      }else{
          $response['name'] = false;
          $response['num'] = false;
      }

    }else {
      $response['name'] = trim(str_replace('-', '', strstr($response['name'], " - ")));
      $response['num'] = explode(" ", $response['name']);
      $response['num'] = $response['num'][0];
      $response['name'] = trim(preg_replace("/{$response['num']}/", '', $response['name'], 1));
    }

    $response['images'] = $this->getImagesInformation($script);
    return $response;
  }

  /**
   * Функция получения информации о страницах манги
   *
   * @param $script array
   *
   * @return array|boolean
   */
  private function getImagesInformation($script)
  {
    preg_match_all('#rm_h.init(.+)]]#iU', $script, $images);
    $images = $images[1][0];
    $images = str_replace("( [[", "", $images);
    $images = explode("],[", $images);
    foreach ($images as $key => $image) {
      $image = str_replace(array('"', '\''), '', $image);
      $image = explode(",", $image);
      $response[$key]['prefix'] = $image[0];
      $response[$key]['server'] = $image[1];
      $response[$key]['path'] = $image[2];
      $response[$key]['link'] = $image[1].$image[0].$image[2];
      $response[$key]['size']['width'] = $image[3];
      $response[$key]['size']['height'] = $image[4];
    }
    if (!isset($response)) {
        $response = false;
    }
    return $response;
  }

  /**
   * Функция скачивания картинок
   *
   * @param $chapters array
   *
   */
  public function getImgMulti($chapters)
  {
    foreach ($chapters as $key => $item) {
      if (isset($item['images'])) {
        $multi_curl = new MultiCurl();
        $multi_curl->setConcurrency(5);
        $multi_curl->success(function($instance) use($item) {
          //  echo 'call to "' . $instance->url . '" was successful.' . "<br>\n";
            $key = array_search($instance->url, array_column($item['images'], 'link'));
            $folderFull = "img/{$this->id}/{$item['id']}/";
            $this->checkDirPath($folderFull);
            Image::make($instance->response)->save("{$folderFull}{$key}.jpg");

        });
        $multi_curl->error(function($instance) {
            echo 'call to "' . $instance->url . '" was unsuccessful.' . "<br>\n";
            echo 'error code: ' . $instance->errorCode . "\n";
            echo 'error message: ' . $instance->errorMessage . "\n";
        });
        foreach ($item['images'] as $img) {
          $multi_curl->addGet($img['link']);
        }
        $multi_curl->start();
      }
    }
  }
}
