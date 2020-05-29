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
$r = $qb
    ->select('id, fileName as alias')
    ->from('turbo_lists')
    ->execute();

while ($f = $r->fetch()) {
    $data[] = $f;
}

echo json_encode(array(
    'success' => true,
    'rows' => $data
));
?>
