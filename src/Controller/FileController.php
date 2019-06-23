<?php
namespace  App\Controller;

use App\Components\ResourceFile;
use App\Entity\File;
use App\Entity\Tags;
use App\Entity\User;
use App\Entity\Courses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use BFS\Exception\IOExceptionInterface;
use BFS\FileSystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\UrlEncode;
use Symfony\Component\HttpFoundation\File\Stream;
use App\Service\RedisService;
use App\Components\UploadFile;
use App\Service\Uploader;


class FileController extends BaseController{
    public function autoAdd($fileName,$courseId){
        $fileName_old=$fileName;
        $fileName_Last=strrchr($fileName, ".");
        $fileName=str_replace(strrchr($fileName_Last,"."),"",$fileName);
        $fileName=str_replace(strrchr($fileName_Last,"("),"",$fileName);
        /**
         * @var $repo \App\Repository\FileRepository
         */
        $repo=$this->getDoctrine()->getRepository('App:File');

        $count=count($repo->getSameFileNameInCourseResource($fileName,$courseId));
        if($count>0){
            $auto_fileName=str_replace(strrchr($fileName, "."),"",$fileName).'('.$count.')'.$fileName_Last;
            return $auto_fileName;
        }else
            return $fileName_old;
    }
    public function getPath($courseId,$fileName)
    {
        $path2 = $this->getUser()->getUsername().$this->getUser()->getId();//获取用户id
        $path3 = $courseId;//获取课程名字
        $path4 = '教学资源';//获取url传入的路径
        return $this->getParameter('data_directory').$path2.'/'.$path3.'/'.$path4.'/'.$fileName.'/';
    }
    /**
     * @Route("/file/upload-check/{courseId}", name="fileUploadCheck")
     */
    public function uploadCheckAction(Request $request,$courseId){
        $md5Path=$this->getParameter('apady_directory').$this->getUser()->getId().'/';
        $md5=$request->request->get('md5');
        $fileName=$this->autoAdd($request->request->get('fileName'),$courseId);
        if(file_exists($md5Path)){
            if(file_exists($md5Path.$md5)){
                $handle=@fopen($md5Path.$md5,'r');
                $file_dir=fgetc($handle);
                return new JsonResponse(['exist'=>1,'file_dir'=>$file_dir,'name'=>$fileName]);
            }
        }
        return new JsonResponse(['exist'=>0,'file_dir'=>NULL,'name'=>$fileName]);
    }
    /**
     * @Route("/file/get-chunks",name="fileGetChunks")
     */
    public function getChunksAction(Request $request,RedisService $redis){
        $redis=$redis->getRedisClient();
        $key=$this->getUser()->getId().$request->request->get('fileName');
        $chunk_array=array();
        if($redis->exists($key)){
            $chunk_array=$redis->smembers($key);
            $len=count($chunk_array);
            $chunk_array['size']=$len;
            for($i=0;$i<$len;$i++){
                $chunk_array[$i]=$chunk_array[$i]-'0';
            }
        }else{
            $chunk_array['size']=0;
        }
        return new JsonResponse($chunk_array);
    }
    /**
     *
     * @Route("/file/upload-index/{courseId}", name="fileUploadIndex")
     */
    public function indexAction(Request $request,Uploader $uploader,$courseId,RedisService $redis)
    {
        if ($request->request->get('newFileName')!=NULL) {
            $fileName = $request->request->get('newFileName');
        }else {
            $fileName = uniqid("file_");
        }

        $realpath= $request->files->get('file')->getPathname();
        $chunk=$request->request->get('chunk');
        if($chunk==NULL){
            $chunk=-1;
        }
        $chunks=$request->request->get('chunks');

        $file=new ResourceFile($this->getPath($courseId,$fileName),$fileName,$chunk,$chunks);
        $file->setCourseID($courseId);
        $file->setUserId($this->getUser()->getId());
        $file->setFileSize($request->request->get('size'));
        $file->setMimeType($request->request->get('type'));
        $file->setFileMd5($request->request->get('md5'));

        $res=$uploader->handle($file,$realpath);

        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($res);
        return $response;

    }
    /**
     * @Route("/file/encode/{fileId}", name="allFileEncode")
     */
    public function encodeFile(RedisService $redis,UrlEncode $urlEncode,$fileId)
    {
        $em = $this->getDoctrine()->getManager();
        $file=$em->getRepository('App:File')
            ->find($fileId);
        $tag=substr(substr(microtime(),0,strripos(microtime(),' ')),strripos(microtime(),'.')+1);
        if($file==NULL)
            throw $this->createNotFoundException('The file does not exist');
        $url=$urlEncode->UrlEncode('123',$file->getId().'/'.$tag);
        $redis->getRedisClient()->set($url,$file->getId(),60*15);
        return new JsonResponse(['url'=>$url]);
    }
    /**
     * @Route("/file/download/{encode_string}", name="downloadFile")
     */
    public function  downFileAction($encode_string,RedisService $redis)
    {
        $redis=$redis->getRedisClient();
        if($redis->get($encode_string)==null)
            return new JsonResponse(['error'=>1]);
        $fileId=$redis->get($encode_string);
        $em = $this->getDoctrine()->getManager();
        $file=$em->getRepository('App:File')
            ->find($fileId);
        if($file==NULL)
            throw $this->createNotFoundException('The file does not exist');

        header('content-type:application/octet-stream');
        header('Accept-Ranges:bytes');
        header('Accept-Length:'.$file->getSize());
        header('content-disposition:attachment;filename='.$file->getFileName());

        $filePath=$this->getParameter('data_directory').$file->getPath().$file->getFileName();
        $handle=fopen($filePath,"r");
        while(!feof($handle)){
            echo fread($handle,4096);
            flush();
        }
        fclose($handle);

        return new JsonResponse(['success'=>1]);
    }
    /**
     * @Route("/file/remove/{fileId}/{courseId}", name="fileRemove")
     */
    public function removeFileAction($fileId=0,$courseId=0)
    {
        try {
            $bfs = new FileSystem($this->getParameter('bfs_flag'));
        }catch (IOExceptionInterface $exception){
            return new JsonResponse(["error"=>$exception->getMessage()]);
        }

        $em = $this->getDoctrine()->getManager();
        $course=$em->getRepository('App:Courses')
            ->find($courseId);
        if ($course==NULL){
            throw $this->createNotFoundException('The course is not existed');
        }
        $em = $this->getDoctrine()->getManager();
        $file=$em->getRepository('App:File')
            ->find($fileId);
        if ($file==NULL){
            throw $this->createNotFoundException('The file is not existed');
        }
        try {
            $bfs->remove($file->getPath().$file->getFileName());
            $bfs->remove($file->getPath());
        }catch (IOExceptionInterface $exception){
            return new JsonResponse(["error"=>$exception->getMessage()]);
        }
        $course->removeResourceFile($file);
        $em = $this->getDoctrine()->getManager();
        $em->remove($file);
        $em->flush();
        return new JsonResponse(["success"=>1]);
    }
    /**
     * @Route("/file/rename/{fileId}/{courseId}", name="fileRename")
     */
    public  function  renameFileAction($fileId=0,$courseId=0,Request $request)
    {
        $fileName=$request->request->get('name');
        $fileName2=str_replace(strrchr($fileName, "."),"",$fileName);
        if($fileName2==null) {
            return new JsonResponse(['success' => 0, 'error' => '文件名为空']);
        }
        try {
            $bfs = new FileSystem($this->getParameter('bfs_flag'));
        }catch (IOExceptionInterface $exception){
            return new JsonResponse(["error"=>$exception->getMessage()]);
        }
        $em = $this->getDoctrine()->getManager();
        $course=$em->getRepository('App:Courses')
            ->find($courseId);
        if ($course==NULL){
            throw $this->createNotFoundException('The course is not existed');
        }
        $em = $this->getDoctrine()->getManager();
        $file=$em->getRepository('App:File')
            ->find($fileId);
        if ($file==NULL){
            throw $this->createNotFoundException('The file is not existed');
        }
        $file->setFileName($fileName.strrchr($file->getFileName(), "."));
        $oldPath=$file->getPath();
        $newFileName=$this->autoAdd($file,$courseId);
        $newPath=substr($oldPath,0,strripos($oldPath,'/')+1).$newFileName;
        try {
            $bfs->rename($oldPath,$newPath);
        }catch (IOExceptionInterface $exception){
            return new JsonResponse(["error"=>$exception->getMessage()]);
        }
        $file->setFileName($newFileName);
        $file->setPath($newPath);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        return new JsonResponse(['success'=>1]);
    }
}