<?php

namespace Bolt\Extension\Its\Portal;

use Doctrine\DBAL\DBALException;
use Silex;

class Suscriptores {

    /** @var \Doctrine\DBAL\Connection */
    public $db;
    public $config;
    public $suscriptorestable;

    /** @var \Silex\Application $app */
    private $app;
    private $suscriptores = array();

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app) {
        $this->app = $app;
        $this->db = $app['db'];
        $this->suscriptorestable = 'suscriptores';
    }

    /**
     * Save changes to a suscriptores to the database. (re)hashing the password, if needed.
     *
     * @param array $suscriptores
     *
     * @return integer The number of affected rows.
     */
    public function saveSuscriptor($suscriptores, $isNew = false) {
        // Make an array with the allowed columns. these are the columns that are always present.
        $allowedcolumns = array(
            'id',
            'email'
        );

        // unset columns we don't need to store.
        foreach (array_keys($suscriptores) as $key) {
            if (!in_array($key, $allowedcolumns)) {
                unset($suscriptores[$key]);
            }
        }

        // Decide whether to insert a new record, or update an existing one.
        if (empty($suscriptores['id'])) {
            return $this->db->insert($this->suscriptorestable, $suscriptores);
        } else {
            return $this->db->update($this->suscriptorestable, $suscriptores, array('id' => $suscriptores['id']));
        }
    }

    /**
     * Remove a user from the database.
     *
     * @param integer $id
     *
     * @return integer The number of affected rows.
     */
    public function deleteSuscriptor($id) {
        $suscriptores = $this->getSuscriptor($id);

        if (empty($suscriptores['id'])) {
            return false;
        } else {
            $res = $this->db->delete($this->suscriptorestable, array('id' => $suscriptores['id']));

            return $res;
        }
    }

    /**
     * Create a stub for a new/empty user.
     *
     * @return array
     */
    public function getEmptySuscriptor() {
        $suscriptores = array(
            'id' => '',
            'email' => ''
        );

        return $suscriptores;
    }

    public function getData($id) {
        return $this->getSuscriptor($id);
    }

    /**
     * Get a user, specified by ID, username or email address. Return 'false' if no user found.
     *
     * @param integer|string $id
     *
     * @return array
     */
    public function getSuscriptor($id, $force = false) {
        // In most cases by far, we'll request an ID, and we can return it here.
        if (!$force && array_key_exists($id, $this->suscriptores)) {
            return $this->suscriptores[$id];
        }

        if (is_numeric($id)) {
            $key = 'id';
        } else {
            $key = 'email';
        }

        // Check if there's already a token stored for this token / IP combo.
        try {
            $query = sprintf('SELECT * FROM %s WHERE ' . $key . '=?', $this->suscriptorestable);
            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
            $suscriptores = $this->db->executeQuery($query, array($id), array(is_numeric($id) ? \PDO::PARAM_INT : \PDO::PARAM_STR))->fetch();
        } catch (DBALException $e) {
            // Oops. Suscriptor will get a warning on the dashboard about tables that need to be repaired.
        }

        // If there's no row, we can't resume a session from the authtoken.
        if (!empty($suscriptores)) {
            $this->suscriptores[$id] = $suscriptores;

            return $suscriptores;
        }

        return false;
    }

    public function getAll($pageSize, $offset) {
        try {
            $sql = 'SELECT * FROM `suscriptores`';

            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($sql, $pageSize, $offset);
            return $this->db->executeQuery($query)->fetchAll();
        } catch (DBALException $e) {
            // Oops. Organization will get a warning on the dashboard about tables that need to be repaired.
        }
        return false;
    }
    
    public function getCount()
    {
        try {
            $query = $this->app['db']->createQueryBuilder()
                            ->select('COUNT(id) as count')
                            ->from($this->suscriptorestable);
            $count = $query->execute()->fetch();

            return (integer) $count['count'];
        } catch (DBALException $e) {
            // Oops. Organization will get a warning on the dashboard about tables that need to be repaired.
        }
        return false;
    }

}
