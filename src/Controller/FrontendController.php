<?php

namespace Bolt\Extension\Its\Portal\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Library as Lib;

/**
 * Controller class.
 *
 * @author Gaston Caldeiro <chugas488@gmail.com>
 */
class FrontendController implements ControllerProviderInterface
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
     * Base route/path is '/example/url'
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];
        
        $ctr->get('/contacto', [$this, 'contacto'])
            ->bind('its-contacto');

        $ctr->post('/contacto', [$this, 'contacto'])
            ->bind('its-contacto-post');

        $ctr->post('/suscribe', [$this, 'addUser'])
            ->bind('suscribe-post');

        return $ctr;
    }

    /**
     * Handles GET requests on /contacto and return a template.
     *
     * @param Request $request
     *
     * @return string
     */
    public function contacto(Application $app, Request $request)
    {
        if($request->isMethod('POST')) {
            $nombre = $request->get('name');
            $email = $request->get('email');
            $phone = $request->get('phone');
            $company = $request->get('company');
            $message = $request->get('msg');

            $subject = 'Contacto [Web]';
            $from = 'ventas@grupobenzo.com';
            //$to = 'chugas488@gmail.com';
            $to = 'jubenzo@gmail.com';

            if($nombre != "" && $email != "" && $message != ""){
                $this->sendEmail($app, $subject, $from, $to, 'mails/contacto.twig', array('name' => $nombre, 'email' => $email, 'body' => $message, 'company' => $company, 'phone' => $phone));
                //$app['session']->getFlashBag()->add('success', 'Tu mensaje ha sido enviado correctamente. Gracias.');
                $jsonResponse = new JsonResponse();

                $jsonResponse->setData([
                    'result' => '1',
                ]);

                return $jsonResponse;
            }

            //$app['session']->getFlashBag()->add('error', 'Tu mensaje no ha sido enviado. Revisa el formulario e intenta enviar el mensaje nuevamente.');
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

    protected function sendEmail($app, $subject, $from, $to, $template, array $data)
    {
        $htmlBody = $app['twig']->render($template, array('data' => $data));

        // Send a welcome email
        $message = $app['mailer']
            ->createMessage('message')
            ->setSubject($subject)
            ->setFrom(array($from => 'Avicolas del Oeste'))
            ->setTo(array($to => $data['name']))
            ->setBody(strip_tags($htmlBody))
            ->addPart($htmlBody, 'text/html');

        return $app['mailer']->send($message);
    }

    public function addUser(Request $request, Application $app) {
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
        if (EMAIL_ADDRESS_VALIDATION_LEVEL == 2) {
            $char = "$unescapedChar";
        } else {
            $char = "($unescapedChar|$escapedChar)";
        };
        $dotString = "$char((\.)?$char){0,63}";

        $qtext = "[\\x01-\\x09\\x0B-\\x0C\\x0E-\\x21\\x23-\\x5B\\x5D-\\x7F]"; # All but <LF> x0A, <CR> x0D, quote (") x22 and backslash (\) x5c
        $qchar = "$qtext|$escapedChar";
        $quotedString = "\"($qchar){1,62}\"";
        if (EMAIL_ADDRESS_VALIDATION_LEVEL == 2) {
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
}
