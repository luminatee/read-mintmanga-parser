<?php
namespace tizis\MangaParser\Information;

/**
 * Родительский клкасс
 */
 
class MangaOwnerController
{
  protected $mature = '?mtr=1&mature=1';
  protected $db;
  public $isProxy;
  function __construct()
  {
    $this->isProxy = false;
  }
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
  protected function checkDirPath($path)
  {
    $path = explode('/', $path);
    if (!empty($path)) {
      $pathCheck = "";
      foreach ($path as $key => $value) {
        $pathCheck .= "{$value}/";
        if (!file_exists($pathCheck)) {
          mkdir($pathCheck, 0700);
        }
        if (!isset($path[$key+1])) {
          break;
        }
      }
    }
  }
}
?>
