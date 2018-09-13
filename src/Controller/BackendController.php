<?php

namespace Bolt\Extension\Its\Portal\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Legacy\Content;

/**
 * Controller class.
 *
 * @author Gaston Caldeiro <chugas488@gmail.com>
 */
class BackendController implements ControllerProviderInterface
{
    /** @var array The extension's configuration parameters */
    private $config;

    /**
     * Initiate the controller with Bolt Application instance and extension config.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Specify which method handles which route.
     *
     * Base route/path is '/bolt'
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        // /example/url/in/controller
        $ctr->get('/newsletter', [$this, 'newsletter'])
            ->bind('newsletter-url'); // route name, must be unique(!)
        $ctr->get('/postulantes', [$this, 'postulantes'])
            ->bind('postulantes-url'); // route name, must be unique(!)
        $ctr->get('/download', [$this, 'download'])
            ->bind('download-url'); // route name, must be unique(!)

        return $ctr;
    }
    
    /**
     * Handles GET requests on /example/url/template and return a template.
     *
     * @param Request $request
     *
     * @return string
     */
    public function newsletter(Application $app, Request $request)
    {
        $count = $app['suscriptores']->getCount();
        $page = $request->get('page_suscriptores', null) ? $request->get('page_suscriptores') : 1;
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        $rows = $app['suscriptores']->getAll($pageSize, $offset);

        $pager = array(
            'for' => 'suscriptores',
            'count' => $count,
            'totalpages' => ceil($count / $pageSize),
            'current' => $page,
            'showing_from' => $offset + 1,
            'showing_to' => $offset + count($rows),
            //'link' => $app['url_generator']->generate('newsletter-url')
        );
        
        $app['storage']->setPager('suscriptores', $pager);
        
        return $app['twig']->render('suscriptores/index.twig', ['rows' => $rows], []);
    }
    
    public function postulantes(Application $app, Request $request)
    {
        $filters = $request->get('form', array());
        $count = $app['postulantes']->getCount($filters);
        $page = $request->get('page_postulantes', null) ? $request->get('page_postulantes') : 1;
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        $rows = $app['postulantes']->getAll($pageSize, $offset, $filters);

        $pager = array(
            'for' => 'postulantes',
            'count' => $count,
            'totalpages' => ceil($count / $pageSize),
            'current' => $page,
            'showing_from' => $offset + 1,
            'showing_to' => $offset + count($rows),
            //'link' => $app['url_generator']->generate('newsletter-url')
        );
        
        $app['storage']->setPager('postulantes', $pager);

        $form = $this->getPostulanteForm($app, array())->getForm();
        $form->submit($request);
        
        return $app['twig']->render('postulantes/index.twig', ['rows' => $rows, 'form'=> $form->createView()], []);
    }
    
    private function getPostulanteForm(Application $app, array $erp) {
        // Start building the form
        $form = $app['form.factory']->createBuilder('form', $erp);
        
        $llamados = $app['storage']->getContent('llamados', array());        
        $areas = $app['storage']->getContent('areas', array());

        $llamadosChoices = array_map(function($record){
            return $record->getTitle();
        }, $llamados);
        $areasChoices = array_map(function($record){
            return $record->getTitle();
        }, $areas);
        
        // Add the other fields
        $form
            ->add('born_from', 'date', array(
                'years' => range(1950, date('Y')),
                'format' => 'ddMMyyyy',
                'empty_value' => '',
                'required' => false,
                'constraints' => array(),
                'label' => 'Fecha desde',
                'attr' => array()
            ))
            ->add('born_to', 'date', array(
                'years' => range(1950, date('Y')),
                'format' => 'ddMMyyyy',
                'empty_value' => '',
                'required' => false,
                'constraints' => array(),
                'label' => 'Fecha hasta',
                'attr' => array()
            ))
            ->add('created_at_from', 'date', array(
                'years' => range(1950, date('Y')),
                'format' => 'ddMMyyyy',
                'empty_value' => '',
                'required' => false,
                'constraints' => array(),
                'label' => 'Fecha desde',
                'attr' => array()
            ))
            ->add('created_at_to', 'date', array(
                'years' => range(1950, date('Y')),
                'format' => 'ddMMyyyy',
                'empty_value' => '',
                'required' => false,
                'constraints' => array(),
                'label' => 'Fecha hasta',
                'attr' => array()
            ))
            ->add('gender', 'choice', array(
                'choices'   => array('m' => 'Masculino', 'f' => 'Femenino', 'o' => 'Otro'),
                'empty_value' => '(Genero)',
                'required' => false,
                'constraints' => array(),
                'label' => 'Genero',
            ))
            ->add('id_llamado', 'text', array(
                'required' => false,
                'constraints' => array(),
                'label' => 'ID llamado',
            ))
            ->add('llamado_id', 'choice', array(
                'choices'   => $llamadosChoices,
                'empty_value' => '(Llamados)',
                'required' => false,
                'constraints' => array(),
                'label' => 'Llamado',
            ))
            ->add('area_id', 'choice', array(
                'choices'   => $areasChoices,
                'empty_value' => '(Areas)',
                'required' => false,
                'constraints' => array(),
                'label' => 'Area',
            ));

        return $form;
    }
       
    /**
     * https://github.com/Thirdwave/bolt-export/blob/0.5.0/src/Controller.php
     * 
     * @param Application $app
     * @param Request     $request
     * @return Response
     */
    public function download(Application $app, Request $request)
    {
        $name  = $request->get('filename', date('Ymd') . '-document');
        $type = $request->get('filetype', 'plain');        
        //$response->setCharset('ISO-8859-1');
        //http://symfony.com/doc/current/components/http_foundation.html
        
        $rows = $app['suscriptores']->getEmails();

        if($type == 'plain') {
            $filename = $name;
            return $this->exportPlain($rows, $filename);
        } else {
            $filename = $name . '.csv';
            return $this->exportCsv($rows, $filename);
        }
    }
    
    private function exportCsv($rows, $filename){       
        $csv = '';
        
        foreach ($rows as $row) {
            $csv .= '"' . implode('"' . "\t" . '"', $row) . '"' . "\n";
        }
        $csv = mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');
        
        return new Response(chr(255) . chr(254) . $csv, 200, array(
          'Content-Description'       => 'File Transfer',
          'Content-Type'              => 'application/vnd.ms-exce',
          'Content-Disposition'       => 'attachment; filename=' . $filename,
          'Content-Transfer-Encoding' => 'binary',
          'Expires'                   => 0,
          'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
          'Pragma'                    => 'public',
          'Content-Length'            => strlen($csv)
        ));
    }
    
    private function exportPlain($rows, $filename) {
        $text = '';
        foreach ($rows as $row) {
            $text .= implode(',', $row) . "\n";
        }
        $text = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
        
        return new Response(chr(255) . chr(254) . $text, 200, array(
          'Content-Description'       => 'File Transfer',
          'Content-Type'              => 'text/plain',
          'Content-Disposition'       => 'attachment; filename=' . $filename,
          'Content-Transfer-Encoding' => 'binary',
          'Expires'                   => 0,
          'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
          'Pragma'                    => 'public',
          'Content-Length'            => strlen($text)
        ));
    }
}
