<?php
ini_set('max_execution_time', 9000);
include 'vendor/autoload.php';

$list = new tizis\MangaParser\MangaSiteController('http://readmanga.me/');
$list->lastPage = false;

var_dump($list->getMangaList('tag', 'manhua'));

$manga = new tizis\MangaParser\MangaController('http://readmanga.me/fairytail');
$manga->malinfo = true;
$manga->chaptersDownloadImg = true;
$manga->chaptersDownloadPoster = true;
$manga->chaptersCountInformationGet = 0;
//$manga->isProxy = true;
$manga->parseManga();
$manga->store();
var_dump($manga->getManga());

