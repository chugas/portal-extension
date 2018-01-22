<?php

namespace Bolt\Extension\Its\Portal\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Library as Lib;
use Symfony\Component\Validator\Constraints as Assert;
use Bolt\Translation\Translator as Trans;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Bolt\Filesystem\UploadContainer;
use Sirius\Upload\Handler as UploadHandler;
use Sirius\Upload\Result\File;

/**
 * Controller class.
 *
 * @author Gaston Caldeiro <chugas488@gmail.com>
 */
class FrontendController implements ControllerProviderInterface {

    /** @var array The extension's configuration parameters */
    private $config;

    /**
     * Initiate the controller with Bolt Application instance and extension config.
     *
     * @param array $config
     */
    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * Specify which method handles which route.
     *
     * Base route/path is '/example/url'
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app) {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        $ctr->get('/contacto', [$this, 'contacto'])
                ->bind('its-contacto');

        $ctr->get('/secret', [$this, 'secret'])
                ->bind('its-secret');

        $ctr->post('/contacto', [$this, 'contacto'])
                ->bind('its-contacto-post');

        $ctr->post('/suscribe', [$this, 'addUser'])
                ->bind('suscribe-post');
        
        $ctr->get('/trabaja-con-nosotros', [$this, 'trabajar'])
                ->bind('its-trabaja-con-nosotros');

        $ctr->post('/trabaja-con-nosotros', [$this, 'trabajar'])
                ->bind('its-trabaja-con-nosotros-post');

        return $ctr;
    }

    /**
     * Handles GET requests on /contacto and return a template.
     *
     * @param Request $request
     *
     * @return string
     */
    public function contacto(Application $app, Request $request) {        
        if ($request->isMethod('POST')) {
            $nombre = $request->get('name');
            $apellido = $request->get('apellido');
            $matricula = $request->get('matricula');
            $email = $request->get('email');
            $phone = $request->get('phone');
            $message = $request->get('msg');

            $subject = 'Contacto [Web]';
            $from = 'noreply@prolesa.com.uy';
            //$to = 'chugas488@gmail.com';
            //$to = 'jubenzo@gmail.com';
            $to = array('info@prolesa.com.uy' => 'Prolesa');
            
            $params = compact('nombre', 'apellido', 'matricula', 'email', 'phone', 'message');
            
            if ($nombre != "" && $apellido != "" && $email != "" && $message != "") {
                $this->sendEmail($app, $subject, $from, $to, 'mails/contacto.twig', $params);

                $jsonResponse = new JsonResponse();

                $jsonResponse->setData([
                    'result' => '1',
                ]);

                return $jsonResponse;
            }

            $jsonResponse = new JsonResponse();

            $jsonResponse->setData([
                'result' => '0',
            ]);
            $jsonResponse->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

            return $jsonResponse;
            //return Lib::redirect('contact', array('v' => -1));
        } else {
            return $app['twig']->render('contacto.twig', [], []);
        }
    }

    protected function sendEmail($app, $subject, $from, $to, $template, array $data, $filename = null) {
        $htmlBody = $app['twig']->render($template, array('data' => $data));

        // Send a welcome email
        $message = $app['mailer']
                ->createMessage('message')
                ->setSubject($subject)
                ->setFrom(array($from => 'Prolesa'))
                ->setTo($to)
                ->setBody(strip_tags($htmlBody))
                ->addPart($htmlBody, 'text/html');

        if($filename != null) {
            $message->attach(\Swift_Attachment::fromPath($filename));
        }
        
        return $app['mailer']->send($message);
    }

    /**
     * Controller for the "Homepage" route. Usually the front page of the website.
     *
     * @param \Silex\Application $app The application/container
     *
     * @return mixed
     */
    public function addUser(Request $request, Application $app) {
        $email = $request->get('email');
        if ($this->validateEmail($email)) {
            try {
                if(!$app['suscriptores']->getSuscriptor($email)) {
                    $app['suscriptores']->saveSuscriptor(array('email' => $email));
                }
                return new JsonResponse(array('status' => 1), 200);
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => 0), 200);
            }
        } else {
            return new JsonResponse(array('status' => 0), 200);
        }
    }
    
    public function addUserPhplist(Request $request, Application $app) {
        $email = $request->get('email');
        if ($this->validateEmail($email)) {
            //$host = 'http://newsletter.avicolasdeloeste.com.uy/';
            //$lista = 'Usuarios';
            try {
                //file_get_contents($host . 'lists/api.php?action=create&e=' . urlencode($email) . '&l=' . $lista . '&v=0');
                return new JsonResponse(array('status' => 1), 200);
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => 0), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return new JsonResponse(array('status' => 0), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validateEmail($email) {

        if (strpos($email, '@')) {
            list($username, $domaincheck) = explode('@', $email);
            # checking for an MX is not sufficient
            #    $mxhosts = array();
            #    $validhost = getmxrr ($domaincheck,$mxhosts);
            $validhost = checkdnsrr($domaincheck, "MX") || checkdnsrr($domaincheck, "A");
        } else {
            $validhost = 0;
        }

        return $validhost && $this->is_email($email);
    }

    public function is_email($email) {
        $email = trim($email);

        ## do some basic validation first
        # quite often emails have two @ signs
        $ats = substr_count($email, '@');
        if ($ats != 1)
            return 0;

        ## fail on emails starting or ending "-" or "." in the pre-at, seems to happen quite often, probably cut-n-paste errors
        if (preg_match('/^-/', $email) ||
                preg_match('/-@/', $email) ||
                preg_match('/\.@/', $email) ||
                preg_match('/^\./', $email) ||
                preg_match('/^\-/', $email) ||
                strpos($email, '\\') === 0
        ) {
            return 0;
        }

        $tlds = 'ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dev|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|home|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|jm|je|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|loc|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw';

        # $email is a valid address as defined by RFC821
        # Except:
        #   Length of domainPart is not checked
        #   Not accepted are CR and LF even if escaped by \
        #   Not accepted is Folding
        #   Not accepted is literal domain-part (eg. [1.0.0.127])
        #   Not accepted is comments (eg. (this is a comment)@example.com)
        # Extra:
        #   topLevelDomain can only be one of the defined ones
        $escapedChar = "\\\\[\\x01-\\x09\\x0B-\\x0C\\x0E-\\x7F]";   # CR and LF excluded for safety reasons
        $unescapedChar = "[a-zA-Z0-9!#$%&'*\+\-\/=?^_`{|}~]";
        //if (EMAIL_ADDRESS_VALIDATION_LEVEL == 2) {
        if (2 == 2) {
            $char = "$unescapedChar";
        } else {
            $char = "($unescapedChar|$escapedChar)";
        };
        $dotString = "$char((\.)?$char){0,63}";

        $qtext = "[\\x01-\\x09\\x0B-\\x0C\\x0E-\\x21\\x23-\\x5B\\x5D-\\x7F]"; # All but <LF> x0A, <CR> x0D, quote (") x22 and backslash (\) x5c
        $qchar = "$qtext|$escapedChar";
        $quotedString = "\"($qchar){1,62}\"";
        //if (EMAIL_ADDRESS_VALIDATION_LEVEL == 2) {
        if (2 == 2) {
            $localPart = "$dotString";  # without escaping and quoting of local part
        } else {
            $localPart = "($dotString|$quotedString)";
        };
        $topLevelDomain = "(" . $tlds . ")";
        $domainLiteral = "((([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))";

        $domainPart = "([a-zA-Z0-9](-?[a-zA-Z0-9])*(\.[a-zA-Z](-?[a-zA-Z0-9])*)*\.$topLevelDomain|$domainLiteral)";
        $validEmailPattern = "/^$localPart@$domainPart$/i"; # result: /^(([a-zA-Z0-9!#$%&'*\+\-\/=?^_`{|}~]|\\[\x01-\x09\x0B-\x0C\x0E-\x7F])((\.)?([a-zA-Z0-9!#$%&'*\+\-\/=?^_`{|}~]|\\[\x01-\x09\x0B-\x0C\x0E-\x7F])){0,63}|"([\x01-\x09\x0B-\x0C\x0E-\x21\x23-\x5B\x5D-\x7F]|\\[\x01-\x09\x0B-\x0C\x0E-\x7F]){1,62}")@([a-zA-Z0-9](-?[a-zA-Z0-9])*(\.[a-zA-Z](-?[a-zA-Z0-9])*)*\.(ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dev|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|home|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|jm|je|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|loc|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|quipu|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)|((([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])))$/i

        if (preg_match($validEmailPattern, $email)) {
            return(1);
        } else {
            return(0);
        }
    }

    public function trabajar(Application $app, Request $request) {
        $form = $this->getTrabajarForm($app, array())->getForm();

        if ($request->isMethod('POST')) {
            $form->submit($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $filename = null;
                $file = $data['archivo'];
                if (!is_null($file)) {
                    $fileToProcess = array(
                        'name' => $file->getClientOriginalName(),
                        'tmp_name' => $file->getPathName()
                    );

                    $result = $this->getUploadHandler($app)->process($fileToProcess);

                    if ($result->isValid()) {
                        $result->confirm();
                        if ($result instanceof File) {
                            $filename = $app['paths']['filespath'] . '/' . $result->name;
                        }
                    }
                }

                $from = 'noreply@prolesa.com.uy';
                //$to = 'chugas488@gmail.com';
                $to = array('recursoshumanos@prolesa.com.uy', 'info@prolesa.com.uy' => 'Prolesa');
                $this->sendEmail($app, 'Postulante [' . $data['position'] . ']', $from, $to, 'mails/trabajar.twig', $data, $filename);

                // Envio email
                return $app['twig']->render('trabajar.twig', ['form'=> $form->createView(), 'v' => 1]);
            }

        }

        return $app['twig']->render('trabajar.twig', ['form'=> $form->createView()]);
    }

    /**
     * 
     * @param Application $app
     * @param array $erp
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getTrabajarForm(Application $app, array $erp) {
        // Start building the form
        $form = $app['form.factory']->createBuilder('form', $erp);

        // Add the other fields
        $form
                ->add('name', 'text', array(
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar tu nombre')))
                    ),
                    'label' => 'Nombre',
                    'attr' => array(
                        'placeholder' => Trans::__('Nombre completo *')
                    )
                ))
                ->add('email', 'text', array(
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar un email'))),
                        new Assert\Email()
                    ),
                    'label' => 'E-mail *',
                    'attr' => array(
                        'placeholder' => Trans::__('E-mail *')
                    )
                ))
                ->add('phone', 'text', array(
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar tu telefono/celular')))
                    ),
                    'label' => 'Teléfono/celular *',
                    'attr' => array(
                        'placeholder' => Trans::__('Teléfono/celular *')
                    )
                ))
                ->add('born', 'date', array(
                    'years' => range(1950, date('Y')),
                    'format' => 'ddMMMMyyyy',
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar tu fecha de nacimiento')))
                    ),
                    'label' => 'Fecha de nacimiento *',
                    'attr' => array(
                        'placeholder' => Trans::__('Fecha de nacimiento *')
                    )
                ))
                ->add('city', 'text', array(
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar tu localidad/ciudad')))
                    ),
                    'label' => 'Localidad/Ciudad *',
                    'attr' => array(
                        'placeholder' => 'Localidad/Ciudad *'
                    )
                ))
                ->add('gender', 'choice', array(
                    'choices'   => array('m' => 'Masculino', 'f' => 'Femenino', 'o' => 'Otro'),
                    'empty_value' => 'Selecciona tu sexo *',
                    'required' => true,
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar tu localidad/ciudad')))
                    ),
                    'label' => 'Sexo *',
                ))
                ->add('position', 'choice', array(
                    'choices'   => array('Administración' => 'Administración', 'Ventas' => 'Ventas', 'Logística' => 'Logística'),
                    'empty_value' => 'Selecciona un área a postular *',
                    'required' => true,
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar tu localidad/ciudad')))
                    ),
                    'label' => 'Selecciona un área a postular *',
                ))
                ->add('description', 'textarea', array(
                    'constraints' => array(
                        new Assert\NotBlank(array('message' => Trans::__('Por favor, debes ingresar una breve descripción')))
                    ),
                    'label' => 'Breve descripción de tu persona *',
                    'attr' => array(
                        'placeholder' => Trans::__('Breve descripción de tu persona *')
                    )
                ))
                ->add('archivo', 'file', array(
                    'label' => 'currículum vitae',
                    'required' => true,
                    'attr' => array(
                        'placeholder' => 'currículum vitae'
                    )
                ));

        return $form;
    }

    public function getUploadHandler(Application $app)
    {
        $app_upload_namespace = 'files';
        $app_upload_prefix = 'cvs/' . date('Y-m') . '/';
        $app_upload_overwrite = false;
        /******************************************************/
        $base = $app['resources']->getPath($app_upload_namespace);
        if (!is_writable($base)) {
            throw new \RuntimeException("Unable to write to upload destination. Check permissions on $base", 1);
        }
        $container = new UploadContainer($app['filesystem']->getFilesystem($app_upload_namespace));
        /******************************************************/
        $allowedExensions = $app['config']->get('general/accept_file_types');
        $uploadHandler = new UploadHandler($container);
        $uploadHandler->setPrefix($app_upload_prefix);
        $uploadHandler->setOverwrite($app_upload_overwrite);
        $uploadHandler->addRule('extension', array('allowed' => $allowedExensions));

        $pattern = $app['config']->get('general/upload/pattern', '[^A-Za-z0-9\.]+');
        $replacement = $app['config']->get('general/upload/replacement', '-');
        $lowercase = $app['config']->get('general/upload/lowercase', true);

        $uploadHandler->setSanitizerCallback(
            function ($filename) use ($pattern, $replacement, $lowercase) {
                if ($lowercase) {
                    return preg_replace("/$pattern/", $replacement, strtolower($filename));
                }

                return preg_replace("/$pattern/", $replacement, $filename);
            }
        );

        return $uploadHandler;
    }
    
    public function secret(){
        $errno; $errstr;
        $host = '190.0.154.163';
        $port = 2525;
        $timeout = 20;
        $options = array();
        $streamContext = stream_context_create($options);        
        $_stream = @stream_socket_client($host.':'.$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $streamContext);
        if (false === $_stream) {
            echo 'Connection could not be established with host '. $host.' ['.$errstr.' #'.$errno.']';
        } else {
            echo 'Connection SUCCESS with host '. $host;
        }
        die();
        
        // Create the Transport
        $transport = (new \Swift_SmtpTransport('190.0.154.163', 2525))
          ->setUsername('noreply@prolesa.com.uy');
          //->setPassword('');

        // Create the Mailer using your created Transport
        $mailer = new \Swift_Mailer($transport);

        // Create a message
        $message = (new \Swift_Message('Wonderful Subject'))
          ->setFrom(['noreply@prolesa.com.uy' => 'Prolesa'])
          ->setTo(['chugas488@gmail.com'])
          ->setBody('Here is the message itself');

        // Send the message
        $result = $mailer->send($message);

        var_dump($result);
        die();
    }
}
