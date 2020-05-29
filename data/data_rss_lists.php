<?php
/************************************************************************************************
 *
 * Список материалов
 *************************************************************************************************/

include_once('common_bo.php');


$data = array();
$rssPath = $application->getServer()->getFullUrl();
if (!preg_match("#/$#", $rssPath))
    $rssPath .= "/";

$qb = Cetera\DbConnection::getDbConnection()->createQueryBuilder();
$sort = !empty($_REQUEST["sort"]) ? $_REQUEST["sort"] : "id";
$dir = !empty($_REQUEST["dir"]) ? $_REQUEST["dir"] : "ASC";
$r = $qb
    ->select('*')
    ->from('turbo_lists')
    ->orderBy($sort, $dir)
    ->execute();

while ($f = $r->fetch()) {
    $f["pathToDinamic"] = $rssPath . RSS_DINAMIC_PATH . "?alias=" . $f["fileName"];

    if (!empty($f["type"]))
        $f["pathToDinamic"] .= "&type=" . $f["type"];

    if ($f["type"] == 5 || $f["type"] == 6) {
        $materialId = \Turbo\RSSFeed::getMaterials($f["id"]);
        $f["pathToDinamic"] .= "&id=" . $materialId[0]["id"];
    }

    $f["pathToDinamic"] = "<a href='" . $f["pathToDinamic"] . "' target='_blank'>" . $f["pathToDinamic"] . "</a>";
    $data[] = $f;
}


echo json_encode(array(
    'success' => true,
    'rows' => $data
));
?>
