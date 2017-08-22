<?php
namespace tizis\MangaParser\Information;

use DiDom\Document;
use Curl\Curl;
use Curl\MultiCurl;
use Intervention\Image\ImageManagerStatic as Image;

require_once $_SERVER["DOCUMENT_ROOT"] . '\lib\jikan.php';

/**
 * Класс получения основной информации о манге
 */

class MangaInformationController extends MangaOwnerController
{
  // Атрибуты данных маинги
  public $id;
  public $name;
  public $url;
  public $authors;
  public $year;
  public $poster;
  public $tags;
  public $genre;
  public $chapters;
  public $chaptersCount;
  public $annotation;
  public $publisher;
  public $magazine;
  public $volumes;
  public $similar;
  public $status;
  public $related;
  public $translator;
  public $malinfo;
  // данные, нужные в след. парсинге данных
  private $urlForParse;
  private $htmlOriginal; // оригинальный html
  private $htmlDom; // дом дерео
  // преднастройки
  private $chaptersDownloadImg; // скачивать картинки или нет
  private $chaptersDownloadPoster; // скачивать постер или нет
  private $chaptersCountInformationGet; // кол-во частей для парсинга

  function __construct($chaptersDownloadImg,
                       $chaptersDownloadPoster,
                       $chaptersCountInformationGet,
                       $malinfo,
                       $url)
  {
    parent::__construct();
    // данные, нужные в след. парсинге данных
    $this->url = $url;
    $this->urlForParse = $url; // mtr=1 = доступ к контету 18+ без подтвержения
    $this->id = substr(parse_url($url, PHP_URL_PATH), 1);
    $this->htmlOriginal = $this->GetContent($this->urlForParse);
    $this->htmlDom = new Document($this->htmlOriginal);
    // преднастройки
    $this->chaptersDownloadImg = $chaptersDownloadImg;
    $this->chaptersDownloadPoster = $chaptersDownloadPoster;
    $this->chaptersCountInformationGet = $chaptersCountInformationGet;
    $this->malinfo = $malinfo;
    // Атрибуты данных маинги
    $this->name = $this->getMangaContentName($this->htmlDom);
    $this->year = $this->getMangaContentYear($this->htmlDom);
    $this->authors = $this->getMangaContentAuthor($this->htmlDom);
    $this->poster = $this->getMangaContentPosters($this->htmlDom);
    $this->tags = $this->getMangaContentTags($this->htmlDom);
    $this->genre = $this->getMangaContentGenre($this->htmlDom);
    $this->annotation = $this->getMangaContentAnnotation($this->htmlDom);
    $this->publisher = $this->getMangaContentPublisher($this->htmlDom);
    $this->magazine = $this->getMangaContentMagazine($this->htmlDom);
    $this->volumes = $this->getMangaContentVolume($this->htmlDom);
    $this->status = $this->getMangaContentStatus($this->htmlDom);
    $this->similar = $this->getMangaContentSimilar($this->htmlDom);
    $this->related = $this->getMangaContentRelated($this->htmlDom);
    $this->chapters = $this->getMangaContentChapters($this->htmlDom);
    $this->translator = $this->getMangaTranslator($this->htmlDom);
    if ($this->malinfo == true) {
      $this->malinfo = $this->getInformationFromMAL($this->name);
    }

  }
  /**
   * Функция скачивания постеров
   */
  public function getPoster()
  {
    if ($this->poster != false) {
      $multi_curl = new MultiCurl();
      $multi_curl->setConcurrency(2);
      $multi_curl->success(function($instance) {
        //  echo 'call to "' . $instance->url . '" was successful.' . "<br>\n";
          $key = array_search($instance->url, array_column($this->poster, 'full')); // находим элемент в массиве по ссылку
          $folderFull = "img/{$this->id}/poster/";
          $this->checkDirPath($folderFull);

          $width = Image::make($instance->response)->width();
          $height = Image::make($instance->response)->height() - 30; // обрезаем вотермарку с постера

          Image::make($instance->response)->crop($width, $height, 0, 0)->save("{$folderFull}{$key}.jpg");
      });
      $multi_curl->error(function($instance) {
          echo 'call to "' . $instance->url . '" was unsuccessful.' . "<br>\n";
          echo 'error code: ' . $instance->errorCode . "\n";
          echo 'error message: ' . $instance->errorMessage . "\n";
      });
      foreach ($this->poster as $key => $item) {
        $multi_curl->addGet($item['full']);
      }
      $multi_curl->start();
    }
  }

  /**
   * Функция получения информации о манги с myanimelist
   *
   * @param $name array
   *
   * @return array|boolean
   */
  public function getInformationFromMAL($name)
  {
    $name = preg_replace('/\s+/', '+', $name['original']);
    $curl = new Curl();
    $curl->setBasicAuthentication('parserNAME1', 'parserNAME1parserNAME1parserNAME1parserNAME1');
    $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
    $curl->get('https://myanimelist.net/api/manga/search/?q='.$name);
    $test = 'https://myanimelist.net/api/manga/search/?q='.$name;

    if ($curl->error) {
      $response['error'] = 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
    } else {
      if (!empty($curl->response)) {
        $response['response'] = $this->xml2array($curl->response);
        $response['response'] = $response['response']['entry'];
        $response['data'] = $response['response'][0];
        $jikan = new \Jikan\Get;
        $response['parser'] = $jikan->manga($response['data']['id'])->data;
      }else{
        $response['error'] = 'mal search result is empty';
      }
    }
    return $response;
  }
  private function xml2array($xml)
  {
    $arr = array();

    foreach ($xml->children() as $r)
    {
      $t = array();
      if(count($r->children()) == 0)
      {
        $arr[$r->getName()] = strval($r);
      }
      else
      {
        $arr[$r->getName()][] = $this->xml2array($r);
      }
    }
    return $arr;
  }
  /**
   * Функция получения имени из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  private function getMangaContentName($html){
    $response['translate'] = $html->first('h1')->first('.name')->text();
    if ($html->first('h1')->has('.eng-name')) {
      $response['original'] = $html->first('h1')->first('.eng-name')->text();
    }else{
      $response['original'] =  $html->first('h1')->first('.name')->text();;
    }
    if ($html->first('h1')->has('.original-name')) {
      $response['jap'] =  $html->first('h1')->first('.original-name')->text();;
    }
    return $response;
  }

  /**
   * Функция получения года из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return int|boolean
   */
  private function getMangaContentYear($html){
    if ($html->has('.elem_year')) {
      return intval($html->first('.elem_year')->first('a')->innerHtml());
    }else{
      return false;
    }
  }

  /**
   * Функция получения автора из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  private function getMangaContentAuthor($html){
    $response = array();
    foreach ($html->find('.elem_author') as $key => $value) {
      $response[$key]['name'] = $value->first('a')->innerHtml();
      $response[$key]['id'] = end(explode('/', $value->first('a')->attr('href')));
    }
    return $response;
  }

  /**
   * Функция получения постеров из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array|boolean
   */
  private function getMangaContentPosters($html){
    $response = array();
    if ($html->has('.picture-fotorama')) {
      foreach ($html->first('.picture-fotorama')->find('img') as $key => $value) {
        $response[$key]['full'] = $value->attr('data-full');
        $response[$key]['thumb'] = $value->attr('data-thumb');
        $response[$key]['original'] = $value->attr('src');
      }
      foreach ($html->first('.picture-fotorama')->find('a') as $key => $value) {
        $count = count($response)+1;
        $response[$count]['full'] = $value->attr('data-full');
        $response[$count]['thumb'] = $value->attr('data-thumb');
        $response[$count]['original'] = $value->attr('href');
      }
    }
    if (!isset($resposnse)) {
      $response = false;
    }
    return $response;
  }

  /**
   * Функция получения тегов из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  private function getMangaContentTags($html){
    $response = array();
    foreach ($html->find('.elem_tag') as $key => $value) {
      $response[$key]['name'] = trim(str_replace(',', '', $value->text()));
      $response[$key]['id'] = end(explode('/', $value->first('a')->attr('href')));
    }
    return $response;
  }

  /**
   * Функция получения жанров из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  private function getMangaContentGenre($html){
    $response = array();
    foreach ($html->find('.elem_genre') as $key => $value) {
      $response[$key]['name'] = trim(str_replace(',', '', $value->text()));
      $response[$key]['id'] = end(explode('/', $value->first('a')->attr('href')));
    }
    return $response;
  }

  /**
   * Функция получения аннотации из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return string
   */
  private function getMangaContentAnnotation($html)
  {
    $response = "";
    if ($html->has('.manga-description')) {
      $response = strip_tags($html->first('.manga-description')->html(), '<p>');
      $response = trim(preg_replace('/(<[^>]+) style=".*?"/i', '$1', $response));
    }
    return $response;
  }

  /**
   * Функция получения издателей из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  private function getMangaContentPublisher($html)
  {
    $response = array();
    foreach ($html->find('.elem_publisher') as $key => $value) {
      $response[$key]['name'] = trim(str_replace(',', '', $value->text()));
      $response[$key]['id'] = end(explode('/', $value->first('a')->attr('href')));
    }
    return $response;
  }

  /**
   * Функция получения журналов из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  private function getMangaContentMagazine($html)
  {
    $response = array();
    foreach ($html->find('.elem_magazine') as $key => $value) {
      $response[$key]['name'] = trim(str_replace(',', '', $value->text()));
      $response[$key]['id'] = end(explode('/', $value->first('a')->attr('href')));
    }
    return $response;
  }

  /**
   * Функция получения кол-во томов из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return int
   */
  public function getMangaContentVolume($html)
  {
    $response = explode(",", $html->first('.subject-meta')->first('p')->text());
    $response = intval(trim(preg_replace("|[^\d]+|", "", $response[0])));
    return $response;
  }
  /**
   * Функция получения переводчиков манги
   *
   * @param $html Document object
   *
   * @return array|boolean
   */
  public function getMangaTranslator($html)
  {
    foreach ($html->find('.elem_translator') as $key => $value)  {
      $response[$key]['name'] = trim(str_replace(',', '', $value->text()));
      $response[$key]['id'] = end(explode('/', $value->first('a')->attr('href')));
    }
    if (!isset($response)) {
     $response = false;
    }
    return $response;
  }
  /**
   * Функция получения статуса из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  public function getMangaContentStatus($html)
  {
    if (mb_strpos($html->first('.subject-meta')->first('p')->text(), 'одолжаетс')
        || (mb_strpos($html->first('.subject-meta')->find('p')[1]->text(), 'одолжаетс'))
    ) {
      $response['name'] = 'active';
      $response['id'] = 1;
    }

    if (mb_strpos($html->first('.subject-meta')->find('p')[1]->text(), 'еревод')) {
      if (mb_strpos($html->first('.subject-meta')->find('p')[1]->text(), 'авершен')) {
        $response['name'] = 'complete';
        $response['id'] = 2;
      }
    }

    if (!isset($response)) {
      $response['name'] = 'unknown';
      $response['id'] = 4;
    }
    return $response;
  }

  /**
   * Функция получения связанного из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  public function getMangaContentRelated($html)
  {
    $response = array();
    foreach ($html->find('.rightBlock') as $value) {
      if ($value->has('h5') && mb_strpos($value->first('h5')->text(), 'вязанные произвед')) {
        foreach ($value->find('li') as $key => $li) {
          if ($li->has('strong')) {
            break;
          }
          if ($li->has('a')) {
            $response[$key]['name'] = trim(str_replace(',', '', $li->first('a')->text()));
            $response[$key]['id'] = end(explode('/', $li->first('a')->attr('href')));
          }
        }
      }
      if ($response) {
        break;
      }
    }
    return $response;
  }

  /**
   * Функция получения похожего из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array
   */
  public function getMangaContentSimilar($html)
  {
    $response = array();
    foreach ($html->find('.rightBlock') as $value) {
      if ($value->has('h5') && mb_strpos($value->first('h5')->text(), 'охожее')) {
        foreach ($value->find('li') as $key => $li) {
          if ($li->has('a')) {
            $response[$key]['name'] = trim(str_replace(',', '', $li->first('a')->text()));
            $response[$key]['id'] = end(explode('/', $li->first('a')->attr('href')));
          }
        }
      }
      if ($response) {
        break;
      }
    }
    return $response;
  }

  /**
   * Функция получения частей из хтмл данных манги
   *
   * @param $html Document object
   *
   * @return array|boolean
   */
  public function getMangaContentChapters($html)
  {
    $response = array();
    if ($html->has('table.table')) {
      $listOfChapters = $html->first('table.table')->find('tr');
      foreach ($listOfChapters as $key => $value) {
        if ($key == 0) {
          continue;
        }
        if ($value->has('a')) {
          $response[$key]['url'] = $value->first('a')->attr('href');

          if ($value->has('.extra-chapter')) {
            $response[$key]['extra'] = true;
          }else {
            $response[$key]['extra'] = false;
          }
        }
      }
    }else {
      $response = false;
    }
    if ($response != false) {
      $this->chaptersCount = count($response);

      foreach ($response as $key => $value) {
        if ($key == 1 && (!mb_strpos($value['url'], 'mtr=1') && !mb_strpos($value['url'], 'mature=1'))) {
          break;
        }else {
          $response[$key]['url'] = str_replace(array('mature=1', 'mtr=1', '?'), '', $value['url']);
        }
      }
      // получение информации о частях
      $chapter = new MangaInformationChapterController(
        $response, $this->id, $this->urlForParse,
        $this->chaptersCountInformationGet,
        $this->chaptersDownloadImg,
        $this->isProxy
      );
      $response = $chapter->getMangaChapters();
    }else{
      $this->chaptersCount = false;
    }
    if ($this->chaptersDownloadPoster != false) {
      $this->getPoster();
    }
    return $response;
  }

}

?>
