<?php

namespace Bolt\Extension\Its\Portal;

use Doctrine\DBAL\DBALException;
use Silex;

class Postulantes {

    /** @var \Doctrine\DBAL\Connection */
    public $db;
    public $config;
    public $postulantestable;

    /** @var \Silex\Application $app */
    private $app;
    private $postulantes = array();

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app) {
        $this->app = $app;
        $this->db = $app['db'];
        $this->postulantestable = 'postulantes';
    }

    /**
     * Save changes to a postulantes to the database. (re)hashing the password, if needed.
     *
     * @param array $postulantes
     *
     * @return integer The number of affected rows.
     */
    public function savePostulante($postulantes) {
        // Make an array with the allowed columns. these are the columns that are always present.
        $allowedcolumns = array(
            'id',
            'nombre',
            'email',
            'telefono',
            'ciudad',
            'fecha_nacimiento',
            'genero',
            'area_id',
            'llamado_id',
            'descripcion',
            'cv',
            'created_at'
        );
        
        $alta = new \DateTime();
        $postulantes['created_at'] = $alta->format('Y-m-d H:i:s');

        // unset columns we don't need to store.
        foreach (array_keys($postulantes) as $key) {
            if (!in_array($key, $allowedcolumns)) {
                unset($postulantes[$key]);
            }
        }

        // Decide whether to insert a new record, or update an existing one.
        if (empty($postulantes['id'])) {
            return $this->db->insert($this->postulantestable, $postulantes);
        } else {
            return $this->db->update($this->postulantestable, $postulantes, array('id' => $postulantes['id']));
        }
    }

    /**
     * Remove a user from the database.
     *
     * @param integer $id
     *
     * @return integer The number of affected rows.
     */
    public function deletePostulante($id) {
        $postulantes = $this->getPostulante($id);

        if (empty($postulantes['id'])) {
            return false;
        } else {
            $res = $this->db->delete($this->postulantestable, array('id' => $postulantes['id']));

            return $res;
        }
    }

    /**
     * Create a stub for a new/empty user.
     *
     * @return array
     */
    public function getEmptyPostulante() {
        $postulantes = array(
            'id' => '',
            'nombre' => '',
            'email' => '',
            'telefono' => '',
            'ciudad' => '',
            'fecha_nacimiento' => '',
            'genero' => '',
            'area_id' => '',
            'llamado_id' => '',
            'descripcion' => '',
            'cv' => ''
        );

        return $postulantes;
    }

    public function getData($id) {
        return $this->getPostulante($id);
    }

    /**
     * Get a user, specified by ID, username or email address. Return 'false' if no user found.
     *
     * @param integer|string $id
     *
     * @return array
     */
    public function getPostulante($id, $force = false) {
        // In most cases by far, we'll request an ID, and we can return it here.
        if (!$force && array_key_exists($id, $this->postulantes)) {
            return $this->postulantes[$id];
        }

        $key = 'id';

        // Check if there's already a token stored for this token / IP combo.
        try {
            $query = sprintf('SELECT * FROM %s WHERE ' . $key . '=?', $this->postulantestable);
            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, 1);
            $postulantes = $this->db->executeQuery($query, array($id), array(\PDO::PARAM_INT))->fetch();
        } catch (DBALException $e) {
            // Oops. Postulante will get a warning on the dashboard about tables that need to be repaired.
        }

        // If there's no row, we can't resume a session from the authtoken.
        if (!empty($postulantes)) {
            $this->postulantes[$id] = $postulantes;

            return $postulantes;
        }

        return false;
    }

    public function getAll($pageSize, $offset, $filters = array()) {
        try {
            $filtros = array();
            if(array_key_exists('area_id', $filters) && $filters['area_id'] != ''){
                $condition = 'p.area_id = ' . $filters['area_id'];
                array_push($filtros, $condition);
            }
            if(array_key_exists('id_llamado', $filters) && $filters['id_llamado'] != ''){
                $condition = 'p.llamado_id = ' . $filters['id_llamado'];
                array_push($filtros, $condition);
            } elseif(array_key_exists('llamado_id', $filters) && $filters['llamado_id'] != ''){
                $condition = 'p.llamado_id = ' . $filters['llamado_id'];
                array_push($filtros, $condition);
            }
            if(array_key_exists('gender', $filters) && $filters['gender'] != ''){
                $condition = 'p.genero = "' . $filters['gender'] . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('created_at_from', $filters) && 
                $filters['created_at_from']['year'] != '' && 
                $filters['created_at_from']['month'] != '' && 
                $filters['created_at_from']['day'] != '') {
                $createdAtFrom = $filters['created_at_from']['year'] . '-' . $filters['created_at_from']['month'] . '-' . $filters['created_at_from']['day'];
                $condition = 'p.created_at >= "' . $createdAtFrom . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('created_at_to', $filters) && 
                $filters['created_at_to']['year'] != '' && 
                $filters['created_at_to']['month'] != '' && 
                $filters['created_at_to']['day'] != '') {
                $createdAtTo = $filters['created_at_to']['year'] . '-' . $filters['created_at_to']['month'] . '-' . $filters['created_at_to']['day'];
                $condition = 'p.created_at <= "' . $createdAtTo . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('born_from', $filters) && 
                $filters['born_from']['year'] != '' && 
                $filters['born_from']['month'] != '' && 
                $filters['born_from']['day'] != '' ) {
                $createdAtFrom = $filters['born_from']['year'] . '-' . $filters['born_from']['month'] . '-' . $filters['born_from']['day'];
                $condition = 'p.fecha_nacimiento >= "' . $createdAtFrom . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('born_to', $filters) && 
                $filters['born_to']['year'] != '' && 
                $filters['born_to']['month'] != '' && 
                $filters['born_to']['day'] != '') {
                $createdAtTo = $filters['born_to']['year'] . '-' . $filters['born_to']['month'] . '-' . $filters['born_to']['day'];
                $condition = 'p.fecha_nacimiento <= "' . $createdAtTo . '"';
                array_push($filtros, $condition);
            }
            
            $where = '';
            if(count($filtros) > 0) {
                $where = ' WHERE ' . implode(' AND ', $filtros);
            }
            
            $sql = 'SELECT p.*, a.title as area, l.title as llamado FROM `postulantes` as p '
                    . 'LEFT JOIN `bolt_llamados` as l ON p.llamado_id = l.id '
                    . 'LEFT JOIN `bolt_areas` as a ON p.area_id = a.id '
                    . $where
                    . ' ORDER BY p.id DESC';

            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($sql, $pageSize, $offset);
            return $this->db->executeQuery($query)->fetchAll();
        } catch (DBALException $e) {
            // Oops. Organization will get a warning on the dashboard about tables that need to be repaired.
        }
        return false;
    }
    
    public function getCount($filters = array())
    {
        try {
            $filtros = array();
            if(array_key_exists('area_id', $filters) && $filters['area_id'] != ''){
                $condition = 'p.area_id = ' . $filters['area_id'];
                array_push($filtros, $condition);
            }
            if(array_key_exists('llamado_id', $filters) && $filters['llamado_id'] != ''){
                $condition = 'p.llamado_id = ' . $filters['llamado_id'];
                array_push($filtros, $condition);
            }
            if(array_key_exists('gender', $filters) && $filters['gender'] != ''){
                $condition = 'p.genero = "' . $filters['gender'] . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('created_at_from', $filters) && 
                $filters['created_at_from']['year'] != '' && 
                $filters['created_at_from']['month'] != '' && 
                $filters['created_at_from']['day'] != '') {
                $createdAtFrom = $filters['created_at_from']['year'] . '-' . $filters['created_at_from']['month'] . '-' . $filters['created_at_from']['day'];
                $condition = 'p.created_at >= "' . $createdAtFrom . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('created_at_to', $filters) && 
                $filters['created_at_to']['year'] != '' && 
                $filters['created_at_to']['month'] != '' && 
                $filters['created_at_to']['day'] != '') {
                $createdAtTo = $filters['created_at_to']['year'] . '-' . $filters['created_at_to']['month'] . '-' . $filters['created_at_to']['day'];
                $condition = 'p.created_at <= "' . $createdAtTo . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('born_from', $filters) && 
                $filters['born_from']['year'] != '' && 
                $filters['born_from']['month'] != '' && 
                $filters['born_from']['day'] != '' ) {
                $createdAtFrom = $filters['born_from']['year'] . '-' . $filters['born_from']['month'] . '-' . $filters['born_from']['day'];
                $condition = 'p.fecha_nacimiento >= "' . $createdAtFrom . '"';
                array_push($filtros, $condition);
            }
            if(array_key_exists('born_to', $filters) && 
                $filters['born_to']['year'] != '' && 
                $filters['born_to']['month'] != '' && 
                $filters['born_to']['day'] != '') {
                $createdAtTo = $filters['born_to']['year'] . '-' . $filters['born_to']['month'] . '-' . $filters['born_to']['day'];
                $condition = 'p.fecha_nacimiento <= "' . $createdAtTo . '"';
                array_push($filtros, $condition);
            }
            
            $query = $this->app['db']->createQueryBuilder()
                            ->select('COUNT(id) as count')
                            ->from($this->postulantestable, 'p');

            if(count($filtros) > 0) {
                $where = implode(' AND ', $filtros);
                $query->where($where);
            }

            $count = $query->execute()->fetch();

            return (integer) $count['count'];
        } catch (DBALException $e) {
            // Oops. Organization will get a warning on the dashboard about tables that need to be repaired.
        }
        return false;
    }
    
    public function getEmails() {
        try {
            $sql = 'SELECT email FROM `postulantes` ORDER BY id DESC';
            return $this->db->executeQuery($sql)->fetchAll();
        } catch (DBALException $e) {
            // Oops. Organization will get a warning on the dashboard about tables that need to be repaired.
        }
        return false;
    }

}
