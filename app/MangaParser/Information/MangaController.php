<?php
namespace tizis\MangaParser;

use DiDom\Document;
use Curl\MultiCurl;
use Intervention\Image\ImageManagerStatic as Image;
use Carbon\Carbon;

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
  protected $db;
  protected $manga;

  function __construct($url)
  {
    $this->db = \ParagonIE\EasyDB\Factory::create(
        'mysql:host=localhost;dbname=manga',
        'root',
        ''
    );
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

  /**
   * Функция сохранения данных в дб
   */
  public function store()
  {
    $datetime = new Carbon();
    if (!empty($this->manga)) {
      foreach ($this->manga->authors as $key => $value) {
        if ($id = $this->db->cell("SELECT id FROM author_data WHERE id_original = ?", $value['id'])) {
          $this->db->update('author_data', [
            'id_original' => $value['id'],
            'name_translate' => $value['name'],
            'updated_at' => $datetime
          ], ['id' => $id]);
        }else {
          $this->db->insert('author_data', [
            'id_original' => $value['id'],
            'name_translate' => $value['name'],
            'created_at' => $datetime,
            'updated_at' => $datetime
          ]);
        }
      }

      if (!empty($this->manga->magazine)) {
        foreach ($this->manga->magazine as $key => $value) {
          if ($id = $this->db->cell("SELECT id FROM magazine_data WHERE id_original = ?", $value['id'])) {
            $this->db->update('magazine_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'updated_at' => $datetime
            ], ['id' => $id]);
          }else {
            $this->db->insert('magazine_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'created_at' => $datetime,
              'updated_at' => $datetime
            ]);
          }
        }
      }

      if (!empty($this->manga->publisher)) {
        foreach ($this->manga->publisher as $key => $value) {
          if ($id = $this->db->cell("SELECT id FROM publisher_data WHERE id_original = ?", $value['id'])) {
            $this->db->update('publisher_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'updated_at' => $datetime
            ], ['id' => $id]);
          }else {
            $this->db->insert('publisher_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'created_at' => $datetime,
              'updated_at' => $datetime
            ]);
          }
        }
      }

      if (!empty($this->manga->tags)) {
        foreach ($this->manga->tags as $key => $value) {
          if ($id = $this->db->cell("SELECT id FROM tag_data WHERE id_original = ?", $value['id'])) {
            $this->db->update('tag_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'updated_at' => $datetime
            ], ['id' => $id]);
          }else {
            $this->db->insert('tag_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'created_at' => $datetime,
              'updated_at' => $datetime
            ]);
          }
        }
      }
      if (!empty($this->manga->genre)) {
        foreach ($this->manga->genre as $key => $value) {
          if ($id = $this->db->cell("SELECT id FROM genre_data WHERE id_original = ?", $value['id'])) {
            $this->db->update('genre_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'updated_at' => $datetime
            ], ['id' => $id]);
          }else {
            $this->db->insert('genre_data', [
              'id_original' => $value['id'],
              'name' => $value['name'],
              'created_at' => $datetime,
              'updated_at' => $datetime
            ]);
          }
        }
      }

      if ($id_manga = $this->db->cell("SELECT id FROM manga_data WHERE id_original = ?", $this->manga->id)) {
        $this->db->update('manga_data', [
          'id_original' => $this->manga->id,
          'name_translate' => $this->manga->name['translate'],
          'name_original' => $this->manga->name['original'],
          'year' => $this->manga->year,
          'id_status' => $this->manga->status['id'],
          'volumes' => $this->manga->volumes,
          'chapters' => $this->manga->chaptersCount,
          'annotation' =>$this->manga->annotation,
          'updated_at' => $datetime
        ], ['id' => $id_manga]);
      }else {
        $this->db->insert('manga_data', [
          'id_original' => $this->manga->id,
          'name_translate' => $this->manga->name['translate'],
          'name_original' => $this->manga->name['original'],
          'year' => $this->manga->year,
          'id_status' => $this->manga->status['id'],
          'volumes' => $this->manga->volumes,
          'chapters' => $this->manga->chaptersCount,
          'annotation' =>$this->manga->annotation,
          'created_at' => $datetime,
          'updated_at' => $datetime
        ]);
        $id_manga = $this->db->cell("SELECT id FROM manga_data WHERE id_original = ?", $this->manga->id);
      }

      if (!empty($this->manga->chapters)) {
        foreach ($this->manga->chapters as $key => $value) {
          if ($id_chapter = $this->db->cell("SELECT id FROM manga_chapter WHERE id_original = ?", $value['url']['path'])) {
            $this->db->update('manga_chapter', [
              'id_original' => $value['url']['path'],
              'id_book' => $id_manga,
              'name' => $value['name'],
              'num' => $value['num'],
              'images_count' => count($value['images']),
              'updated_at' => $datetime
            ], ['id' => $id_chapter]);
          }else {
            $this->db->insert('manga_chapter', [
              'id_original' => $value['url']['path'],
              'id_book' => $id_manga,
              'name' => $value['name'],
              'num' => $value['num'],
              'images_count' => count($value['images']),
              'created_at' => $datetime,
              'updated_at' => $datetime
            ]);
          }
        }
      }
    }
  }
}
?>
