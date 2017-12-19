<?php

/**
 * Created by PhpStorm.
 * User: Paula
 * Date: 29/07/15
 * Time: 11:15
 */

namespace Bolt\Extension\Its\Portal\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Controller\Base;

class InfiniteScrollController extends Base {

    //private $app;

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app) {
        $this->app = $app;
        $ctr = $app['controllers_factory'];
        $ctr->match('/{contenttypeslug}', [$this, 'infiniteScroll'])
                ->method('GET');
        $ctr->match('/{contenttypeslug}', [$this, 'listing'])
                ->method('GET');
        $ctr->match('/{taxonomytype}/{slug}', [$this, 'taxonomy'])
                ->method('GET');
        return $ctr;
    }
    
    protected function addRoutes(ControllerCollection $c){
        
    }

    public function infiniteScroll($contenttypeslug, Request $request) {
        $contenttype = $this->app['storage']->getContenttype($contenttypeslug);
        if (empty($contenttype)) {
            return 'Contenido invalido';
        }

        if ($request->isMethod('GET')) {
            $page = $request->request->get('page');

            $amount = (!empty($contenttype['listing_records']) ? $contenttype['listing_records'] : $this->app['config']->get('general/listing_records'));
            $order = (!empty($contenttype['sort']) ? $contenttype['sort'] : $this->app['config']->get('general/listing_sort'));
            $content = $this->app['storage']->getContent($contenttype['slug'], array('limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true));

            if (empty($contenttype['infinitescroll_template'])) {
                $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__) . '/../templates');
                $template = 'listing_ajax.html.twig';
            } else {
                $template = $contenttype['infinitescroll_template'];
            }

            $globals = [
                'records' => $content,
                $contenttype['slug'] => $content,
                'contenttype' => $contenttype['name']
            ];

            if ($content) {
                return $this->app['twig']->render($template, $globals);
            } else {
                return 'No hay mas registros';
            }
        }
    }

    /**
     * The listing page controller.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return TemplateResponse
     */
    public function listing(Request $request, $contenttypeslug) {
        $listingparameters = $this->getListingParameters($contenttypeslug);
        $content = $this->getContent($contenttypeslug, $listingparameters);
        $contenttype = $this->getContentType($contenttypeslug);

        if (empty($contenttype['infinitescroll_template'])) {
            $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__) . '/../templates');
            $template = 'listing_ajax.html.twig';
        } else {
            $template = $contenttype['infinitescroll_template'];
        }

        // Make sure we can also access it as {{ pages }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'records' => $content,
            $contenttypeslug => $content,
            'contenttype' => $contenttype['name'],
        ];

        if ($content) {
            return $this->app['twig']->render($template, $globals);
        } else {
            return '';
        }
    }

    /**
     * The taxonomy listing page controller.
     *
     * @param Request $request      The Symfony Request
     * @param string  $taxonomytype The taxonomy type slug
     * @param string  $slug         The taxonomy slug
     *
     * @return TemplateResponse|false
     */
    public function taxonomy(Request $request, $taxonomytype, $slug) {
        $taxonomy = $this->app['storage']->getTaxonomyType($taxonomytype);
        // No taxonomytype, no possible content.
        if (empty($taxonomy)) {
            return false;
        }
        $taxonomyslug = $taxonomy['slug'];

        // First, get some content
        $context = $taxonomy['singular_slug'] . '_' . $slug;
        $page = $this->app['pager']->getCurrentPage($context);
        // Theme value takes precedence over default config @see https://github.com/bolt/bolt/issues/3951
        $amount = $this->getOption('theme/listing_records', false) ?: $this->getOption('general/listing_records');

        // Handle case where listing records has been override for specific taxonomy
        if (array_key_exists('listing_records', $taxonomy) && is_int($taxonomy['listing_records'])) {
            $amount = $taxonomy['listing_records'];
        }

        $order = $this->getOption('theme/listing_sort', false) ?: $this->getOption('general/listing_sort');
        $content = $this->app['storage']->getContentByTaxonomy($taxonomytype, $slug, ['limit' => $amount, 'order' => $order, 'page' => $page]);

        $template = 'partials/_news.twig';

        // Get a display value for slug. This should be moved from 'slug' context key to 'name' in v4.0.
        $name = $slug;
        if ($taxonomy['behaves_like'] !== 'tags' && isset($taxonomy['options'][$slug])) {
            $name = $taxonomy['options'][$slug];
        }

        $globals = [
            'records' => $content,
            'slug' => $name,
            'taxonomy' => $this->getOption('taxonomy/' . $taxonomyslug),
            'taxonomytype' => $taxonomyslug,
        ];

        if ($content) {
            return $this->app['twig']->render($template, $globals);
        } else {
            return '';
        }
    }

}
