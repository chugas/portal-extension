<?php

namespace Bolt\Extension\Its\Portal;

use Bolt\Extension\SimpleExtension;
use Bolt\Extension\Its\Portal\Controller\FrontendController;
use Bolt\Extension\Its\Portal\Controller\BackendController;

/**
 * Portal extension class.
 *
 * @author Gaston Caldeiro <chugas488@gmail.com>
 */
class PortalExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return ['templates'];
    }

    public function registerTwigFilters()
    {
        return [
            'chunk'  => 'chunk'
        ];
    }

    public function registerTwigFunctions()
    {
        return [
            'recetasrelacionadas'  => 'recetasrelacionadas',
        ];
    }
    
    /**
     * @param array $input
     * @param int $size
     *
     * @return mixed
     */
    public function chunk($input, $size)
    {
        return array_chunk($input, $size);
    }
    
    /**
     * @param $category
     * @return mixed
     */
    public function recetasrelacionadas($category, $id) {
        /*$app = $this->getContainer();
        $query = "SELECT bolt_recetas.* 
                FROM bolt_recetas WHERE `id` IN (
                    SELECT content_id AS id 
                    FROM bolt_taxonomy 
                    WHERE `bolt_taxonomy`.`content_id` != " . $id . " AND `bolt_taxonomy`.`contenttype` = 'recetas' AND `bolt_taxonomy`.`taxonomytype` = 'categoriesrecetas' AND `bolt_taxonomy`.`slug` = '" . $category . "' 
                ) AND `bolt_recetas`.`status` = 'published' 
                LIMIT 3";

        $rows = $app['db']->executeQuery($query)->fetchAll();

        $ids = implode(' || ', util::array_pluck($rows, 'id'));
        $results = $app['storage']->getContent('recetas', array('id' => $ids, 'returnsingle' => false));

        return $results;*/
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * Mount the ExampleController class to all routes that match '/example/url/*'
     *
     * To see specific bindings between route and controller method see 'connect()'
     * function in the ExampleController class.
     */
    protected function registerFrontendControllers()
    {
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/' => new FrontendController($config),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/extend' => new BackendController($config),
        ];
    }
}
