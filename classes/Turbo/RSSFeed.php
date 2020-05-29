<?php

namespace Turbo;

class RSSFeed extends \Cetera\Base
{
    use \Cetera\DbConnection;
    protected $_name;
    protected $_url;
    protected $_limit;

    public static function enum()
    {
        return new Iterator();
    }

    public static function fetch($data)
    {
        return new self($data);
    }

    public static function getById($id)
    {
        $t = \Cetera\Application::getInstance()->getTranslator();
        $qb = self::getDbConnection()->createQueryBuilder();
        $data = $qb
            ->select('*')
            ->from('turbo_lists')
            ->where($qb->expr()->eq('id', $id))
            ->execute();

        if ($f = $data->fetch())
            return new self($f);

        throw new \Exception($t->_('Turbo с ID=') . $id . $t->_(' не найден'));
    }


    public static function startImport(\Cetera\Application $application, $id, $materialId = 0, $type = 2)
    {
        $qb = self::getDbConnection()->createQueryBuilder();
        $data = $qb
            ->select('*')
            ->from('turbo_lists')
            ->where($qb->expr()->eq('id', $id))
            ->execute();

        if ($f = $data->fetch()) {
            if ($materialId !== 0) {
                $materials = self::getMaterial($materialId, $f['dirs']);
            } else {
                $materials = self::getMaterials($f['id']);
            }

            self::doImport($materials, $application->getServer(), $type);
        }

    }

    public static function getMaterial($id, $dirs)
    {
        $dirs = json_decode($dirs);
        foreach ($dirs as $dirId) {
            $list = \Cetera\Catalog::getById($dirId)->getMaterials()->where("id=:id")
                ->setParameter("id", $id);
            $material = $list->current();
            if ($material) {
                $m = $material->fields;
                $m['path'] = preg_replace("#([^:])//#is", "$1/", $material->getCatalog()->getUrl());
                $m['fullPath'] = preg_replace("#([^:])//#is", "$1/", $material->getFullUrl());
                return [$material];
            }
        }
        return [];
    }

    public static function getMaterials($id)
    {
        $materials = array();

        // Цикл по разделам
        $qb = self::getDbConnection()->createQueryBuilder();
        $data = $qb
            ->select('dirs')
            ->from('turbo_lists')
            ->where($qb->expr()->eq('id', $id))
            ->execute();

        $dirArray = Array();
        if ($f = $data->fetch()) {
            $dirs = trim(preg_replace("#(\[|\])#is", "", $f["dirs"]));
            $dirs = explode(",", $dirs);

            if (is_array($dirs)) {
                $dirArray = $dirs;
            }
        }

        if (count($dirArray)) {
            foreach ($dirArray as $id) {
                if (empty($id))
                    continue;

                $qb = self::getDbConnection()->createQueryBuilder();
                $data = $qb
                    ->select('A.id, B.alias')
                    ->from('dir_data A, types B')
                    ->where($qb->expr()->eq('A.id', $id) . " AND B.id=A.typ")
                    ->execute();

                while ($f = $data->fetch()) {
                    $idcat = $f["id"];
                    $table = $f["alias"];

                    $catalogItem = \Cetera\Catalog::getById($idcat);
                    $idList = Array(
                        $idcat
                    );

                    foreach ($catalogItem->children as $child) {
                        $idList[] = $child->id;
                    }

                    foreach ($idList as $idcat) {
                        // цикл по материалам
                        $qb = self::getDbConnection()->createQueryBuilder();
                        $mdata = $qb
                            ->select("*")
                            ->from($table)
                            ->where("type&" . MATH_PUBLISHED . "1" . " AND idcat=" . $idcat)
                            ->orderBy("dat", "DESC");


                        $mdata = $mdata->execute();

                        while ($fields = $mdata->fetch()) {
                            if ($material = \Cetera\Material::getById($fields["id"], $fields["type"], $table)) {
                                $m = $material->fields;
                                $m['path'] = preg_replace("#([^:])//#is", "$1/", $material->getCatalog()->getUrl());
                                $m['fullPath'] = preg_replace("#([^:])//#is", "$1/", $material->getFullUrl());
                                $materials[] = $m;
                            }
                        }
                    }
                }
            }
        }

        return $materials;
    }

    public static function doImport(array $materials = Array(), $server, $type = 4, $yandex = 0)
    {
        reset($materials);

        switch (intval($type)) {
            case 4:
                $rssType = FeedWriter::Turbo;
                break;
            case 5:
                $rssType = FeedWriter::AMP;
                break;
            case 6:
                $rssType = FeedWriter::Telegram;
                break;
        }

        $serverFields = $server->fields;

        $serverFields["fullPath"] = preg_replace("#([^:])//#is", "$1/", $server->getFullUrl());

        $feed = new FeedWriter($rssType);

        $feed->setTitle($serverFields["name"]);
        $feed->setLink($serverFields["fullPath"]);
        if (!empty($serverFields["meta_description"]))
            $feed->setDescription($serverFields["meta_description"]);

        if (!empty($serverFields["pic"])) {
            $feed->setImage($serverFields["name"], $serverFields["fullPath"], preg_replace("#([^:])//#is", "$1/", $serverFields["fullPath"] . $serverFields["pic"]));
        }

        if (!empty($serverFields["meta_description"]))
            $feed->setDescription($serverFields["meta_description"]);

        if (!empty($serverFields["pic"]) && file_exists(DOCROOT . $serverFields["pic"])):
            $image = preg_replace("#([^:])//#is", "$1/", $serverFields["fullPath"] . $serverFields["pic"]);
            $feed->setImage($serverFields["name"], $serverFields["fullPath"], $image);
        endif;
        foreach ($materials as $material):
            //создаем пустой item
            $newItem = $feed->createNewItem();
            $timestamp = strtotime($material["dat"]);
            $rss_datetime = date(DATE_RFC822, $timestamp);
            $ampDate = explode(' ', $material["dat"]);

            //добавляем в него информацию

            if ($rssType != FeedWriter::AMP && $rssType != FeedWriter::Telegram) {
                $newItem->setTitle($material['name']);
                $newItem->setLink($material['fullPath']);
                $newItem->setDate($rss_datetime);
                $short = mb_convert_encoding(!empty($material['short']) ? $material['short'] : $material['text'], 'UTF-8');
            }


            if (!empty($short))
                $newItem->setDescription($short);




            if ($rssType == FeedWriter::Turbo) {
                $newItem->turboContent('<![CDATA[' . '<header><h1>' . $material['name'] . '</h1></header>' . $material['text'] . ']]>');
            }

            if ($rssType == FeedWriter::AMP) {
                $newItem->ampContent('<h1>' . $material['name'] . '</h1>' . PHP_EOL . '<amp-img src="' . $material['pic'] . '" alt="' . $material['name'] . '" width="600" height="400" 
                    layout="responsive"></amp-img>' . PHP_EOL . '<p><strong>' . $ampDate[0] . '</strong></p>' . PHP_EOL . $material['text']);



                //Получаем путь до материала
                if ($mat = \Cetera\Material::getById($material["id"], 1, "materials")) {
                    $m = $material->fields;
                    $m['path'] = preg_replace("#([^:])//#is", "$1/", $material->getCatalog()->getUrl());
                    $m['fullPath'] = preg_replace("#([^:])//#is", "$1/", $material->getFullUrl());
                }
            }

            if ($rssType == FeedWriter::Telegram) {
                $newItem->ampContent('<h1 class="telegram-title">' . $material['name'] . '</h1>' . PHP_EOL .
                    '<div class="telegram-content">' . PHP_EOL . $material['text'] . PHP_EOL . '</div>' . PHP_EOL);

                //Получаем путь до материала
                if ($mat = \Cetera\Material::getById($material["id"], 1, "materials")) {
                    $m = $material->fields;
                    $m['path'] = preg_replace("#([^:])//#is", "$1/", $material->getCatalog()->getUrl());
                    $m['fullPath'] = preg_replace("#([^:])//#is", "$1/", $material->getFullUrl());
                }
            }

            //теперь добавляем item в наш канал
            $feed->addItem($newItem);
        endforeach;

            ob_start();
            $feed->genarateFeed($yandex, $m);

            $rss = ob_get_contents();
            ob_get_clean();

            echo $rss;

    }


    public static function startImportAll(\Cetera\Application $application)
    {
        $qb = self::getDbConnection()->createQueryBuilder();
        $data = $qb
            ->select('*')
            ->from('turbo_lists')
            ->execute();

        while ($f = $data->fetch()) {
            $materials = self::getMaterials($f['id']);
            self::doImport($materials, $application->getServer());
        }
    }

    //Записать ссылку в Header
    public static function writeLink($type) {
        $url = $_SERVER['REQUEST_URI'];
        $pos = strpos($url, "?" );
        if ($pos) {
            $url = strstr($url, '?', true);
        }
        $materialAlias = explode("/", $url);


        if (!$materialAlias[count($materialAlias)-1]) {
            $materialCategory = $materialAlias[count($materialAlias)-3];
            $materialAlias = $materialAlias[count($materialAlias)-2];
        } else {
            $materialCategory = $materialAlias[count($materialAlias)-2];
            $materialAlias = $materialAlias[count($materialAlias)-1];
        }


        //Получим ID категории в которой лежит материал
        $qb = self::getDbConnection()->createQueryBuilder();
        $data = $qb
            ->select('id')
            ->from('dir_data')
            ->where('tablename ="'.$materialCategory.'"')
            ->execute();
        while ($f = $data->fetch()) {
            $categoryID = $f["id"];
        }

        //Получаем Id материала
        $qb = self::getDbConnection()->createQueryBuilder();
        $data = $qb
            ->select('id')
            ->from('materials')
            ->where('alias ="'.$materialAlias.'" AND idcat = "'.$categoryID.'"')
            ->execute();
        while ($f = $data->fetch()) {
            $material = $f;
        }


        /******Получаем ID всех AMP страниц на сайте********/
        $qb = self::getDbConnection()->createQueryBuilder();
        $data = $qb
            ->select('`dirs`')
            ->from('turbo_lists')
            ->add('where', 'type = '.$type)
            ->execute();
        while ($f = $data->fetch()) {
            $AMPids[] = $f;
        }

        if (!$AMPids) return;

        //Убираем [] вокруг dirs
        foreach ($AMPids as $key => $AMPid) {
            $AMPids[$key] = str_replace("[", "", $AMPid);
            $AMPids[$key] = str_replace("]", "", $AMPids[$key]);
        }
        //Выбираем все Материалы AMP
        $allAmp = array();
        foreach ($AMPids as $AMPid) {
            $qb = self::getDbConnection()->createQueryBuilder();
            $data = $qb
                ->select('`id`')
                ->from('materials')
                ->where('idcat ="' . $AMPid["dirs"] . '"')
                ->orderBy('id', 'DESC')
                ->execute();
            while ($f = $data->fetch()) {
                $allAmp[] = $f;
            }
        }


            //Выбираем все Материалы AMP
            $qb = self::getDbConnection()->createQueryBuilder();
            $data = $qb
                ->select('`id`')
                ->from('materials')
                ->where('idcat ="' . $categoryID . '"')
                ->orderBy('id', 'DESC')
                ->execute();
            while ($f = $data->fetch()) {
                $AMPmaterials[] = $f;
            }

            //Ищем входит ли наш материал в массив AMP материалов
            if ($allAmp) {
                $inAmp = false;
                foreach ($allAmp as $materialItem) {
                    if ($materialItem["id"] == $material["id"]) {
                        $inAmp = true;
                    }
                }
            }


            //Если наш материал находится в AMP получаем ссылку на AMP версию и добавляем ее в <head></head>
            if ($inAmp) {
                //формуируем ссылку для ГуглАМП
                $qb = self::getDbConnection()->createQueryBuilder();
                $data = $qb
                    ->select('`fileName`')
                    ->from('turbo_lists')
                    ->where('dirs = "[' . $categoryID . ']" AND type = '.$type)
                    ->execute();
                while ($f = $data->fetch()) {
                    $alias = $f;
                }

                $ampurl = $_SERVER[HTTP_X_FORWARDED_PROTO] . "://" . $_SERVER[HTTP_HOST] . "/plugins/turbo/data/rss.php?alias=" . $alias["fileName"] . "&type=".$type. "&id=" . $material["id"];

                $app = \Cetera\Application::getInstance();
                if($type == 5) {
                    $rel = "amphtml";
                } elseif ($type = 6) {
                    $rel = "canonical";
                }
                $app->addHeadString('<link href="' . $ampurl . '" rel="'.$rel.'">');
            }

    }




}