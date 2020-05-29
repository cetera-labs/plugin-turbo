<?php
$t = $this->getTranslator();
$t->addTranslation(__DIR__.'/lang');


define('GROUP_RSS', -103);

define('RSS_LIST_FORMED', 1);
define('RSS_LIST_SCHEDULED', 2);
define('RSS_LIST_SENDING', 3);
define('RSS_LIST_DONE', 4);
define('RSS_LIST_PAUSED', 5);
define('RSS_LIST_CANCELED', 6);

define('RSS_LIST_SCD_OFF', 0);
define('RSS_LIST_SCD_DAY', 1);
define('RSS_LIST_SCD_WEEK', 2);
define('RSS_LIST_SCD_MONTH', 3);

define('MAX_RCPTS', 10);

define('RSS_SEND_OK', 1);
define('RSS_NOTHING_TO_SEND', 2);
define('RSS_ERROR', 3);


define("RSS_DINAMIC_PATH", "plugins/turbo/data/rss.php");

$this->addUserGroup(array(
    'id' => GROUP_MAIL,
    'name' => $translator->_('Пользователи Turbo ленты'),
    'describ' => $translator->_('Имеют доступ к управлению Turbo лентой'),
));

if ($this->getBo() && $this->getUser() && $this->getUser()->hasRight(GROUP_RSS)) {
    $this->getBo()->addModule(array(
        'id' => 'turbo',
        'position' => MENU_SITE,
        'name' => $translator->_('Турбо-страницы'),
        'icon' => '/plugins/turbo/images/icon.png',
        'class' => 'Plugin.turbo.RSSListPanel'
    ));
}

if ($this->isFrontOffice()) {
    \Turbo\RSSFeed::writeLink(5);
    \Turbo\RSSFeed::writeLink(6);
}