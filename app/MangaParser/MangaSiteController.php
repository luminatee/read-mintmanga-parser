<?php
namespace tizis\MangaParser;

use DiDom\Document;

/**
 *
 */

class MangaSiteController
{

  public $url;
  public $pages;
  private $currentType;
  public $lastPage;
  private $name;
  function __construct($url)
  {
    $this->url = $url;
    $this->lastPage = 70;

  }
  /**
   * функция получения списка
   *
   * @param string $type
   *
   * @return array
   */
  public function getMangaList($type, $name = "")
  {
    $this->name = $name;
    return $this->getMangaListPages($this->url, $type);
  }
  /**
   * функция получения данных
   *
   * @param array $response
   *
   * @param string $url
   *
   * @return array
   */
  private function getMangaListElements($response, $url)
  {
    $html = $this->GetContent($url);
    $html = new Document($html);

    $count = count($response) + 1;

    if ($this->currentType == 'mangas') {
      foreach ($html->find('.tiles  .tile') as $key => $value) {
        $response[$count] = $value->first('a')->attr('href');
        $count++;
      }
    }
    if ($this->currentType == 'tag'){
      foreach ($html->find('.tiles  .tile') as $key => $value) {
        $response[$count] = $value->first('a')->attr('href');
        $count++;
      }
    }
    if ($this->currentType == 'authors'){
      foreach ($html->find('.person-link') as $key => $value) {
        $response[$count] = $value->attr('href');
        $count++;
      }
    }

    if ($this->currentType == 'genres'){
      foreach ($html->find('.element-link') as $key => $value) {
        $response[$count] = $value->attr('href');
        $count++;
      }
    }

    return $response;
  }
  /**
   * функция получения списка страниц/элементов
   *
   * @param string $domain
   *
   * @param string $type
   *
   * @return array|boolean
   */
   private function getMangaListPages($domain, $type)
    {
     $this->currentType = $type;
     if ($type == 'mangas') {
        $link_sub = '/list?type=&sortType=updated&';
      }
      if ($type == 'tag') {
        $link_sub = '/list/tag/'.$this->name.'?type=&sortType=rate&';
      }
      if ($type == 'authors') {
        $link_sub = '/list/authors/sort_name?';
      }
      if ($type == 'genres') {
        $link_sub = '/list/genres/sort_name?';
      }
      if (isset($link_sub)) {
        $link = "{$domain}{$link_sub}offset=0&max=70";
        $html = $this->GetContent($link);

        $html = new Document($html);

        if ($html->has('.pagination a')) {
          $lastPage = $html->first('.pagination')->find('a')[4]->attr('href');
          preg_match_all('/(&offset=[^&]+)/i', $lastPage, $lastPage);
          $lastPage = preg_replace("|[^\d]+|", "", $lastPage[0][0]);

          if ($this->lastPage != false) {
            $lastPage = $this->lastPage;
          }
          for ($i=0; $i <= $lastPage; $i = $i + 70) {
            $pages[] = "{$domain}{$link_sub}offset={$i}&max=70";
          }
        }else {
          $pages[] = "{$domain}{$link_sub}";
        }
        if (isset($pages)) {
          $elements = array();
          foreach ($pages as $key => $value) {
            $elements = $this->getMangaListElements($elements, $value);
          }
        }
        if (isset($elements)) return $elements;
      }
      return false;
    }
    /**
     * функция получения html данных
     *
     * @param string $url
     *
     * @return string
     */
    protected function GetContent($url)
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $response = curl_exec($ch);
      curl_close ($ch);
      return $response;
    }
  /**
   * функция скачивания сайтмапа
   *
   * @param string $url
   *
   */
    public function getSitemap($url)
    {
      $response = $this->GetContent($url);
      $myfile = fopen("sitemap.xml", 'wb');
      fwrite($myfile, "\n". $response);
      fclose($myfile);
    }
}
?>
