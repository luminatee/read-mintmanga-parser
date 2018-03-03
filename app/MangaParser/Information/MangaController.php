<?php
namespace tizis\MangaParser;

use DiDom\Document;
use Curl\MultiCurl;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Контроллер манги
 */

class MangaController
{
  public $chaptersDownloadImg = false;
  public $chaptersDownloadPoster = false;
  public $chaptersCountInformationGet = 10;
  public $malinfo = false;
  protected $url;
  protected $manga;

  function __construct($url)
  {
    $this->url = $url;
  }

  /**
   * Функция парсинга манги
   */
  public function parseManga()
  {
    $this->manga = new Information\MangaInformationController(
      $this->chaptersDownloadImg,
      $this->chaptersDownloadPoster,
      $this->chaptersCountInformationGet,
      $this->malinfo,
      $this->url
    );
  }

  /**
   * Функция получения атрибутов из объекта манги: имя, статус и тд
   *
   * @param $name string
   *
   * @return array
   */
  public function get($name)
  {
    if (isset($this->manga->$name)) {
      $response = $this->manga->$name;
    }else {
      $response['error'] = "ERROR: attr \"{$name}\" is not exists";
    }
    return $response;
  }

  /**
   * Функция всей информации о манге
   *
   * @return array
   */
  public function getManga()
  {
    if (!empty($this->manga)) {
      $response['chapters'] = $this->manga->chapters;
      $response['name'] = $this->manga->name;
      $response['id'] = $this->manga->id;
      $response['url'] = $this->manga->url;
      $response['author'] = $this->manga->authors;
      $response['year'] = $this->manga->year;
      $response['poster'] = $this->manga->poster;
      $response['tags'] = $this->manga->tags;
      $response['genre'] = $this->manga->genre;
      $response['annotation'] = $this->manga->annotation;
      $response['publisher'] = $this->manga->publisher;
      $response['magazine'] = $this->manga->magazine;
      $response['volumes'] = $this->manga->volumes;
      $response['similar'] = $this->manga->similar;
      $response['status'] = $this->manga->status;
      $response['related'] = $this->manga->related;
      $response['translator'] = $this->manga->translator;
      $response['malinfo'] = $this->manga->malinfo;
    }else {
      $response['error'] = "ERROR: manga is not parse";
    }
    return $response;
  }
}
