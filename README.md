# Парсер для readmanga.me and mintmanga.com
Parser for ru manga resources - readmanga.me and mintmanga.com. Parser is not finished because the plans have changed in the development process.

Парсер для readmanga.me and mintmanga.com. Парсер незакончен т.к. в процессе разработки поменялись планы.

РАЗРАБОТКА БЫЛА БРОШЕНА НА ПОЛПУТИ, потому использовать на свой страх и риск. Код в большинстве случаях сопровожден комментариями, потому разобраться что да как не сложно.

Использованы библиотеки:

* https://github.com/jikan-me/jikan - для парсинга информации с myanimelist.net
* https://github.com/Imangazaliev/DiDOM - для разбора дом дерева
* https://github.com/php-curl-class/php-curl-class - для многопоточного curl
* https://github.com/Intervention/image - для работы с изображениями.

Парсер позволяет получить информацию как о манге, там и списки по тегам, авторам, манги. См. код.
Пример списка:
```php
$list = new tizis\MangaParser\MangaSiteController('http://readmanga.me/');
$list->lastPage = false;
var_dump($list->getMangaList('tag', 'manhua'));
```
Пример получения манги:
```php
$manga = new tizis\MangaParser\MangaController('http://readmanga.me/fairytail');
$manga->malinfo = true;
$manga->chaptersDownloadImg = true;
$manga->chaptersDownloadPoster = true;
$manga->chaptersCountInformationGet = 0;
//$manga->isProxy = true;
$manga->parseManga();
var_dump($manga->getManga());
var_dump($manga->get('name'));
```
