<?
define('DOCROOT', $_SERVER['DOCUMENT_ROOT'] . '/');
require_once(DOCROOT . "cms/include/common.php");

$application = Cetera\Application::getInstance();
$application->connectDb();
$application->initSession();
$application->initPlugins();
$application->ping();

$alias = trim($_REQUEST["alias"]);
$materialId = intval($_REQUEST['id']);
$type = intval($_REQUEST["type"]);


if (!empty($alias)) {
    $qb = Cetera\DbConnection::getDbConnection()->createQueryBuilder();
    $r = $qb
        ->select('id, fileName')
        ->from('turbo_lists')
        ->where($qb->expr()->eq('fileName', $qb->expr()->literal($alias, PDO::PARAM_STR)))
        ->execute();

    if ($f = $r->fetch()) {
        \Turbo\RSSFeed::startImport($application, $f['id'], $materialId, $type);
    }
}
?>