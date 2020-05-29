<?
namespace Turbo;

class Lists {

    public static function delete($id) {
        $qb = \Cetera\DbConnection::getDbConnection()->createQueryBuilder();
        $r = $qb
            ->delete("turbo_lists")
            ->where($qb->expr()->eq('id', $id))
            ->execute();

        return true;
    }

    public static function select($id, $name) {
        $qb = \Cetera\DbConnection::getDbConnection()->createQueryBuilder();
        $r = $qb
            ->select('id')
            ->from('turbo_lists')
            ->where($qb->expr()->eq('name', $qb->expr()->literal($name)))
            ->andWhere("id <> " . $id)
            ->execute();

        return $r;
    }

    public static function save($id, $name, $fieldname, $dirs, $type) {
        $qb = \Cetera\DbConnection::getDbConnection()->createQueryBuilder();
        if ($id) {
            $r = $qb
                ->update('turbo_lists')
                ->set('`name`', $qb->expr()->literal($name, \PDO::PARAM_STR))
                ->set('`fileName`', $qb->expr()->literal($fieldname, \PDO::PARAM_STR))
                ->set('`dirs`', $qb->expr()->literal($dirs, \PDO::PARAM_STR))
                ->set('`type`', $type)
                ->where($qb->expr()->eq('id', $id))
                ->execute();
        } else {
            $r = $qb
                ->insert('turbo_lists')
                ->values(
                    array(
                        '`name`' => $qb->expr()->literal($name, \PDO::PARAM_STR),
                        '`fileName`' => $qb->expr()->literal($fieldname, \PDO::PARAM_STR),
                        '`dirs`' => $qb->expr()->literal($dirs, \PDO::PARAM_STR),
                        '`type`' => $type,
                    )
                )
                ->execute();
        }

        if (!$id)
            $id = \Cetera\DbConnection::getDbConnection()->lastInsertId();

        return $id;
    }

    public static function get($id) {
        $qb = \Cetera\DbConnection::getDbConnection()->createQueryBuilder();
        $r = $qb
            ->select('*')
            ->from('turbo_lists')
            ->where($qb->expr()->eq('id', $id))
            ->execute();

        return $r;
    }

}