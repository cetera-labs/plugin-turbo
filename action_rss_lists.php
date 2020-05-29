<?php

include_once('common_bo.php');


$res = array(
    'success' => false,
    'errors' => array()
);


$action = $_POST['action'];
$sel = $_POST['sel'];
$id = (int)$_POST['id'];




if ($action == 'delete_list') {
    $res['success'] = \Turbo\Lists::delete($id);
}

if ($action == 'save_list') {
    try {
        $dirs = trim(preg_replace("#(\[|\])#is", "", $_REQUEST["dirs_parse"]));
        $dirs = explode(",", $dirs);

        $dirData = Array();
        if (is_array($dirs)) {
            foreach ($dirs as $dir) {
                if (is_numeric($dir))
                    $dirData[] = $dir;
            }
        }
        $_REQUEST["dirs"] = "[" . implode(",", $dirData) . "]";

        $r = \Turbo\Lists::select($id, $_POST['name']);

        if ($r->fetch())
            throw new \Exception($translator->_('Turbo с таким названием уже существует'));

        $r = \Turbo\Lists::select($id, $_POST['fileName']);

        if ($r->fetch())
            throw new \Exception($translator->_('Turbo с таким Alias\'ом уже существует'));


        $id = \Turbo\Lists::save($id, $_REQUEST["name"], $_REQUEST["fileName"], $_REQUEST["dirs"], $_REQUEST["type"]);

        $res['success'] = true;
    } catch (\Exception $e) {
        $res["errors"][] = $e->getMessage();
    }
}

if ($action == 'get_list') {


    $r = \Turbo\Lists::get((int)$_REQUEST['id']);

    $res['rows'] = $r->fetch();

    $dirs = trim(preg_replace("#(\[|\])#is", "", $res['rows']["dirs"]));
    $dirs = explode(",", $dirs);

    $dirData = Array();
    if (is_array($dirs)) {
        foreach ($dirs as $dir) {
            if (is_numeric($dir))
                $dirData[] = $dir;
        }
    }
    $res['rows']["dirs"] = "[" . implode(",", $dirData) . "]";

    $res['success'] = true;
}

if ($action == 'import_item') {
    \Turbo\RSSFeed::startImport($application, $id);
}

if ($action == 'import_all') {
    \Turbo\RSSFeed::startImportAll($application);
}


echo json_encode($res);
?>
