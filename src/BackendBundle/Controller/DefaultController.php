<?php

namespace BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BackendBundle\Form\UserForm;
use BackendBundle\Entity\User;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

use Fortytwo\SDK\AdvancedMessagingPlatform\AdvancedMessagingPlatform;
use Fortytwo\SDK\AdvancedMessagingPlatform\Entities\DestinationEntity;
use Fortytwo\SDK\AdvancedMessagingPlatform\Entities\SMSContentEntity;
use Fortytwo\SDK\AdvancedMessagingPlatform\Entities\RequestBodyEntity;

class DefaultController extends MainController
{
    /**
     * @Route("/admin", name="admin_dashboard")
     */
    public function indexAction()
    {
        if($this->checkAuthAdmin() == false)
        {
            return $this->redirectToRoute('admin_login');
        }
        // replace this example code with whatever you need
        return $this->render('backend/default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
    public function registerAction(Request $request)
    {
        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserForm::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $user->setUserType(1);
            //$user->setRole('ADMIN_ROLE');
            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            /*try
            {*/
                $em->persist($user); 
                $em->flush();
            /*}
            catch(\Exception $e)
            {
                var_dump($e->getMessage());
            }
            exit;*/
            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user
            return $this->redirectToRoute('admin_login');
        }

        return $this->render(
            'backend/default/register.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("sms-gateway-test")
     */
    public function smsGatewayTest()
    {
        $fortytwo_base_uri = 'https://rest.fortytwo.com/1/sms';
        $token = '4fdde82c-03f3-442a-9978-2fee0ed86942';

        /*$json = json_encode([
            'destinations' => [
                ['number' => '919874886276']
            ],
            'sms_content' => [
                "message" => "This is a real test message to say hello."
            ]
        ]);

        $ch = curl_init($fortytwo_base_uri);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Token ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $curl_response = curl_exec($ch);
        $info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        dump($json, $curl_response);

        return new Response($info);*/

        try {
            $guzzle_client = new GuzzleClient();
            $response = $guzzle_client->request('POST', $fortytwo_base_uri, [
                'json' => [
                    'destinations' => [
                        ['number' => '919874886276']
                    ],
                    'sms_content' => [
                        "message" => "This is a real test message to say hello."
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Token ' . $token
                ]
            ]);

            $body = $response->getBody();
            $result = json_decode($body);
        }
        catch (RequestException $e) {
            $req = Psr7\str($e->getRequest());
            $result['req'] = $req;
            if ($e->hasResponse()) {
                $res = Psr7\str($e->getResponse());
                $result['res'] = $res;
            }
        }
        catch (\Exception $e) {
            $result = $e->getMessage();
            dump($result);
        }
        return new JsonResponse($result);
    }

    /**
     * @Route("sms-gateway-test-by-sdk")
     */
    public function smsGatewayTestBySdk()
    {
        $TOKEN = '4fdde82c-03f3-442a-9978-2fee0ed86942';
        $NUMBER = '919874886276';

        try {
            $messaging = new AdvancedMessagingPlatform($TOKEN);

            //Set destination
            $destination = new DestinationEntity();
            $destination->setNumber($NUMBER);

            //SMS Content
            $SMS = new SMSContentEntity();

            $SMS
                ->setMessage('This is a test SMS message from Fortytwo.')
                ->setSenderId('Fortytwo');

            $request = new RequestBodyEntity();

            $request
                ->addDestination($destination)
                ->setSmsContent($SMS);

            $response = $messaging->sendMessage($request);

            $result = $response->getResultInfo()->getDescription() ."\n";

        } catch (\Exception $e) {
            $result = $e->getMessage();
        }
        return new Response($result);
    }
}
