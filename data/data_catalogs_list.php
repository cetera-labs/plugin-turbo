<?php
/************************************************************************************************
 *
 * Список материалов
 *************************************************************************************************/

include_once('common_bo.php');

$data = array();

$list_id = (int)$_REQUEST['id'];

$qb = Cetera\DbConnection::getDbConnection()->createQueryBuilder();
$r = $qb
    ->select('dirs')
    ->from('turbo_lists')
    ->where($qb->expr()->eq('id', $list_id))
    ->execute();

if ($f = $r->fetch()) {
    try {
        $dirs = trim(preg_replace("#(\[|\])#is", "", $f["dirs"]));
        $dirs = explode(",", $dirs);

        if (is_array($dirs)) {
            foreach ($dirs as $dir) {
                if (is_numeric($dir)) {
                    $c = \Cetera\Catalog::getById(intval($dir));
                    $dirItem = Array(
                        "id" => intval($dir)
                    );
                    foreach ($c->getPath() as $item) {
                        if ($item->isRoot())
                            continue;
                        if ($dirItem['name'])
                            $dirItem['name'] .= ' / ';

                        $dirItem['name'] .= $item->name;
                    }

                    if (!empty($dirItem['name']))
                        $data[] = $dirItem;
                }
            }
        }
    } catch (Exception $e) {
    }
}

echo json_encode(array(
    'success' => true,
    'total' => sizeof($data),
    'rows' => $data
));
?>
