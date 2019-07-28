<?php
namespace  App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use App\Service\RedisService;
use App\Service\UrlEncode;
/**
 * @Route("/s", name="share")
 * @IsGranted("ROLE_USER")
 */
class FileShareController extends BaseController
{
    /**
     * @Route("/get-url/{fileId}", name="GenerateURL")
     */
    public function shareFile($fileId=0,UrlEncode $urlEncode,RedisService $redis,Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $file=$em->getRepository('App:File')
            ->find($fileId);
        if($file==NULL)
            throw $this->createNotFoundException('The file does not exist');
        $tag=substr(substr(microtime(),0,strripos(microtime(),' ')),strripos(microtime(),'.')+2);
        $key=substr(uniqid(),8,12);
        $url=$urlEncode->UrlEncode($key,$file->getId().'/'.$tag);
        $time=$request->request->get('time');
        $redis->getRedisClient()->set($url,$key,$time*60*60*24);
        return new JsonResponse([
            "url"=>$url,
            "key"=>$key,
        ]);
    }
    /**
     * @Route("/{encode_string}", name="LinkInput")
     */
    public function fileShareInputAction($encode_string)
    {

        return $this->render('key.html.twig', array(
            "isShare"=>1,
            "encode_string"=>$encode_string));
    }
    /**
     * @Route("/submit/{encode_string}", name="LinkSubmit")
     */
    public function fileShareSubmitAction($encode_string,RedisService $redis,Request $request)
    {
        $redis=$redis->getRedisClient();
        if($redis->get($encode_string)==null)
            return new JsonResponse(["error"=>1]);
        $key=$request->request->get('key');
        if($redis->get($encode_string)!=$key)
            return new JsonResponse(["error"=>2]);
        return new JsonResponse(["url"=>$encode_string]);
    }
}