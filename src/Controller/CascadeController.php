<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/cascade", methods={"GET"})
 */
class CascadeController extends AbstractController
{
    /**
     * @Route("/", name="cascade_index")
     */
    public function indexAction()
    {
        $url = $this->generateUrl('cascade_first', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $dataFGC = json_decode(
            file_get_contents(
                $url, false,
                stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ])
            ),
            true
        );

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
        ]);
        $dataCurl = json_decode(curl_exec($curl), true);
        curl_close($curl);

        return new JsonResponse([
            'success' => true,
            'message' => 'Cascade finished',
            'stream_get_contents' => $dataFGC,
            'cURL' => $dataCurl,
        ]);
    }

    /**
     * @Route("/first-cascade", name="cascade_first")
     */
    public function subcascadeAction()
    {
        $url = $this->generateUrl('cascade_second', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $dataFGC = json_decode(
            file_get_contents(
                $url, false,
                stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ])
            ),
            true
        );

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
        ]);
        $dataCurl = json_decode(curl_exec($curl), true);
        curl_close($curl);

        return new JsonResponse([
            'success' => true,
            'message' => 'Cascade finished',
            'stream_get_contents' => $dataFGC,
            'cURL' => $dataCurl,
        ]);
    }

    /**
     * @Route("/second-cascade", name="cascade_second")
     */
    public function subsubcascadeAction()
    {
        usleep(500000);

        return new JsonResponse([
            'success' => true,
            'message' => 'Cascade finished',
            'stream_get_contents' => 'Hello world!',
        ]);
    }
}