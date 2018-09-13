<?php

namespace Bolt\Extension\Its\Portal;

use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Bolt\Extension\Its\Portal\Controller\FrontendController;
use Bolt\Extension\Its\Portal\Controller\BackendController;
use Bolt\Extension\Its\Portal\Controller\InfiniteScrollController;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Twig_Markup;
use Bolt\Extension\Its\Portal\Suscriptores;
use Bolt\Extension\Its\Portal\Postulantes;
use Bolt\Menu\MenuEntry;
use Bolt\Extension\Its\Portal\Storage;

/**
 * Portal extension class.
 *
 * @author Gaston Caldeiro <chugas488@gmail.com>
 */
class PortalExtension extends SimpleExtension {

    protected function registerMenuEntries() {
        $menu = MenuEntry::create('newsletter-menu', 'newsletter')
                ->setLabel('Suscriptores')
                ->setIcon('fa:newspaper-o')
                ->setPermission('dashboard')
                ->setRoute('newsletter-url')
        ;
        $menu2 = MenuEntry::create('postulantes-menu', 'postulantes')
                ->setLabel('Postulantes')
                ->setIcon('fa:users')
                ->setPermission('dashboard')
                ->setRoute('postulantes-url')
        ;
        
        return [
            $menu, $menu2
        ];
    }

    protected function registerServices(Application $app) {
        $app['suscriptores'] = $app->share(function ($app) {
            return new Suscriptores($app);
        });
        $app['postulantes'] = $app->share(function ($app) {
            return new Postulantes($app);
        });
        $app['its_storage'] = $app->share(function ($app) {
            return new Storage($app);
        });
    }

    protected function registerAssets() {
        $start = (new Javascript())
                ->setFileName('js/start.js')
                ->setLate(true)
                ->setZone(Zone::FRONTEND);

        $jscroll = (new Javascript())
                ->setFileName('js/jscroll/jquery.jscroll.min.js')
                ->setLate(true)
                ->setZone(Zone::FRONTEND);

        return [
            $start,
            $jscroll
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths() {
        return ['templates'];
    }

    public function registerTwigFilters() {
        return [
            'chunk' => 'chunk'
        ];
    }

    public function registerTwigFunctions() {
        return [
            'recetasrelacionadas' => 'recetasrelacionadas',
            'infiniteScroll' => 'twigInfiniteScroll',
            'mail_footer' => ['mailFooter', ['is_safe' => ['html']]],
        ];
    }

    public function mailFooter() {
        return $this->renderTemplate('mails/mail_footer.twig');
    }

    public function twigInfiniteScroll() {
        $html = '<div id="infinite-scroll"></div>
                <div id="infinite-scroll-bottom"></div>';

        return new Twig_Markup($html, 'UTF-8');
    }

    /**
     * @param array $input
     * @param int $size
     *
     * @return mixed
     */
    public function chunk($input, $size) {
        return array_chunk($input, $size);
    }

    /**
     * @param $category
     * @return mixed
     */
    public function recetasrelacionadas($category, $id) {
        /* $app = $this->getContainer();
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

          return $results; */
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
    protected function registerFrontendControllers() {
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/' => new FrontendController(),
            '/infinitescroll' => new InfiniteScrollController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers() {
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/extend' => new BackendController($config),
        ];
    }

}
