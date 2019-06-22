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
    public function autoAdd(File $file,$courseId){
        $fileName=$file->getFileName();
        $fileName_Last=strrchr($file->getFileName(), ".");
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
            return $file->getFileName();
    }

    /**
     * @Route("/file/upload-check/{courseId}", name="fileUploadCheck")
     */
    public function uploadCheckAction(Request $request,$courseId){
        $fileName=$request->request->get('fileName');
        $fileName2=str_replace(strrchr($fileName, "."),"",$fileName);
        if($fileName2==null) {
           return new JsonResponse(['fileName'=>false]);
        }
        $em = $this->getDoctrine()->getManager();
        $course=$em->getRepository('App:Courses')
            ->find($courseId);
        $em = $this->getDoctrine()->getManager();
        $file=$em->getRepository('App:File')
            ->findOneByPath(
                '/'.$this->getUser()->getUsername().$this->getUser()->getId().'/'.$course->getName().'/教学资源/'.$fileName
            );
        if($file!=null){
           // $this->autoAdd($file,$courseId);
            return new JsonResponse(['success'=>1,'name'=>$this->autoAdd($file,$courseId)]);
        }
        $dir =$this->getParameter('apady_directory').$this->getUser()->getId().$fileName2;
        if(!file_exists($dir))
            return new JsonResponse(['type'=>0]);
        $md5list_path=$dir.'/md5list.txt';
        if(!file_exists($md5list_path))
            return new JsonResponse(['type'=>1]);
        $md5File = @file($md5list_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if($md5File==NULL)
            return new JsonResponse(['type'=>1]);
        $block_info=scandir($dir);
        foreach ($block_info as $key => $block) {
            if ($block == '.' || $block == '..'||$block=='md5list.txt')
                unset($block_info[$key]);
            else{
//                $md5=mymd5($dir."/".$block);
//                if(!in_array($md5,$md5File)){
//                    @unlink($dir."/".$block);
//                    unset($block_info[$key]);
//                }else{
//                    array_push($record_md5,$md5);
//                }
            }
        }
//        @unlink($md5list_path);
//        file_put_contents($md5list_path, join($record_md5, "\n"));
        $count_block=count($block_info);
        return  new JsonResponse(['block_count'=>$count_block,'type'=>1]);
    }
    /**
     * @Route("/file/md5", name="fileMd5")
     */
    public function recordMd5Action(Request $request)
    {
        $fileName=$request->request->get('fileName');
        $block_md5=$request->request->get('md5');
        $fileName2=str_replace(strrchr($fileName, "."),"",$fileName);
        $uploadDir=$this->getParameter('apady_directory').$this->getUser()->getId().$fileName2."/";
        if (!file_exists($uploadDir))
            mkdir ($uploadDir,0755,true);
        $md5list_path=$uploadDir.'md5list.txt';
        $md5File = @file($md5list_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $md5File = $md5File ? $md5File : array();
        if(in_array($block_md5,$md5File))
            return new JsonResponse(["exist"=>1]);
        else {
            array_push($md5File, $block_md5);
            $md5File = array_unique($md5File);
            file_put_contents($md5list_path, join($md5File, "\n"));
            return new JsonResponse(["success" => 1]);
        }
    }
    /**
     * 接受文件分块
     * 提交post请求 包括：
     * 当前是第几块文件分块:chuck
     * 当前文件分块的md5值:md5
     * 文件名:filename
     * 临时文件名:temp_filename
     * 成功接到分片就返回1
     *
     * @Route("/file/upload-index", name="fileUploadIndex")
     */
    public function indexAction(Request $request,Uploader $uploader)
    {
        if ($request->request->get('name')!=NULL) {
            $fileName = $request->request->get('name');
        } elseif ($request->files->get('fileName')!=NULL) {
            $fileName = $request->files->get('fileName');
        } else {
            $fileName = uniqid("file_");
        }
        $fileName2=str_replace(strrchr($fileName, "."),"",$fileName);
        $chunk=$request->request->get('chunk');
        $uploadDir=$this->getParameter('apady_directory').$this->getUser()->getId().$fileName2."/";
        if (!file_exists($uploadDir))
            mkdir ($uploadDir,0755,true);
        $file=$request->files->get('file');
        $realpath=$file->getPathname();

        if($request->request->get('chunk')==NULL) {
            move_uploaded_file($realpath, $uploadDir.$fileName);
        }
        else {
            move_uploaded_file($realpath,$uploadDir.$chunk);
        }

        $md5list_path=$uploadDir.'md5list.txt';
        $md5File = @file($md5list_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $md5File = $md5File ? $md5File : array();

        /**
         * To do list.
         * 如下是课程资源是上传样例
         */

        $file=new ResourceFile($uploadDir.$fileName);
        $file->setCourseID(12345);
        $file->setUserId(122);
        $file->setFileSize(1234);
        $file->setMimeType('type');

        //$file=new UploadFile($uploadDir.$fileName);
        $res=$uploader->handle($file,$uploadDir.$chunk);

        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($res);
        return $response;

//        function mymd5( $file ) {
//            $fragment = 65536;
//            $rh = fopen($file, 'rb');
//            $size = filesize($file);
//            $part1 = fread( $rh, $fragment );
//            fseek($rh, $size-$fragment);
//            $part2 = fread( $rh, $fragment);
//            fclose($rh);
//            return md5( $part1.$part2 );
//        }
//        if($request->request->get('chunk')!=NULL){
//            $isin=in_array(mymd5($uploadDir.$chunk),$md5File);
//            if(!$isin){
//                return new JsonResponse(["success"=>"0"]);
//            }
//        }else{
//            $isin=in_array(mymd5($uploadDir.$fileName),$md5File);
//            if(!$isin){
//                return new JsonResponse(["success"=>"0"]);
//            }
//        }
//        return new JsonResponse(["success"=>"1"]);
    }
    /**
     * 这一步是合并文件分块
     * 提交Post请求：
     * 文件名:filename
     * 临时文件名:temp_filename
     * 文件的md5值(不是文件分块的md5值):md5
     * 文件大小:size
     * 文件种类:type
     * 课程id:id
     * 上传的路径:dir
     * 成功merge返回1
     *
     * @Route("/file/upload-merge", name="fileUploadMerge")
     */
    public function mergeFileAction(Request $request){
        $fileName=$request->request->get('fileName');
        $fileName2=str_replace(strrchr($fileName, "."),"",$fileName);
        $chunk_array=scandir($this->getParameter('apady_directory').$this->getUser()->getId().$fileName2);

        foreach ($chunk_array as $key => $chunk) {
            if ($chunk == '.' || $chunk == '..') unset($chunk_array[$key]);
        }
        natsort($chunk_array);

        $uploadDir=$this->getParameter('apady_directory')."/".$this->getUser()->getId().$fileName2."/";
        $saveFileDir=$this->getParameter('apady_directory').'saveFile/';
        if(!file_exists($saveFileDir)){
            mkdir($saveFileDir,0755);
        }
        $out=@fopen($saveFileDir.$fileName,"wb");
        if (!$out)
            return new JsonResponse(["success"=>"0"]);
        if(flock($out,LOCK_EX)){
            foreach($chunk_array as $chunk){
                $in=fopen($uploadDir.$chunk,"rb");
                if(!$in)
                    break;
                while($buff=fread($in,4096)){
                    fwrite($out,$buff);
                }

                @fclose($in);
                @unlink($uploadDir.$chunk);
            }
        }
        flock($out, LOCK_UN);

        @fclose($out);
       @rmdir($uploadDir);

        function mymd5( $file ) {
            $fragment = 65536;
            $rh = fopen($file, 'rb');
            $size = filesize($file);
            $part1 = fread( $rh, $fragment );
            fseek($rh, $size-$fragment);
            $part2 = fread( $rh, $fragment);
            fclose($rh);
            return md5( $part1.$part2 );
        }
        if($request->request->get('md5')!=mymd5($saveFileDir)) {
            return new JsonResponse(["success"=>"1"]);
        }else{
            return new JsonResponse(["success"=>"0"]);
        }
    }
    /**
     * 将文件信息persist到数据库
     * 将文件persist BFS
     * 成功persist返回1
     *
     * @Route("/file/upload-persist/{courseId}", name="fileUploadPersist",requirements={"courseId"="\d+"})
     */
    public function persistFileAction(Request $request,$courseId){
        $em = $this->getDoctrine()->getManager();
        $course=$em->getRepository('App:Courses')
            ->find($courseId);
        if ($course==NULL){
            throw $this->createNotFoundException('The course is not existed');
        }
        $path2 = $this->getUser()->getUsername().$this->getUser()->getId();//获取用户id
        $path3 = $course->getName();//获取课程名字
        $path4 = '教学资源';//获取url传入的路径
        $fileName=$request->request->get('fileName');
        $targetdir = "/".$path2 . "/" . $path3 . "/" . $path4 . "/" . $fileName;
        $saveFileDir=$this->getParameter('apady_directory').'saveFile/'.$fileName;
        try {
            $bfs = new FileSystem($this->getParameter('bfs_flag'));
            $bfs->put($saveFileDir, $targetdir);
        }catch (IOExceptionInterface $exception){
            return new JsonResponse(["error"=>$exception->getMessage()]);
        }
        $file=new File();
        $file->setUser($this->getUser());
        $file->setMimeType($request->request->get('fileType'));
        $file->setSize($request->request->get('fileSize'));
        $file->setFileName($fileName);
        $file->setPath($targetdir);
        $em->persist($file);
        $course->addResourceFile($file);
        $em->flush();
        @unlink($saveFileDir);
       return new JsonResponse(["success"=>"1"]);

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
            throw $this->createNotFoundException('The course does not exist');
        $url=$urlEncode->UrlEncode('123',$file->getId().'/'.$tag);
        $redis->getRedisClient()->set($url,$file->getId(),60*60*15);
        return new JsonResponse(['url'=>$url]);
    }
    /**
     * @Route("/file/download/{encode_string}", name="downloadFile")
     */
    public function  downFileAction($encode_string,UrlEncode $urlEncode,RedisService $redis)
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
        $downloadDir=$this->getParameter('apady_directory').'downloadFile/';
        if(!file_exists($downloadDir)){
            mkdir($downloadDir,0755);
        }
        $downloadFileDir=$downloadDir.$file->getFileName()."/";
        if(!file_exists($downloadFileDir)){
            mkdir($downloadFileDir,0755);
        }
        $targetdir=$file->getPath();
        try {
            $bfs = new FileSystem($this->getParameter('bfs_flag'));
            touch($downloadFileDir.$file->getFileName());
            $stream=new Stream($downloadFileDir.$file->getFileName());
            $response = new BinaryFileResponse($stream);

            $response->headers->set('Content-Type', 'text/plain');
            $response->setContentDisposition ( ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFileName());
            $bfs->get($targetdir, $downloadFileDir);
            return $response;
        }catch (IOExceptionInterface $exception){
            return new JsonResponse(["error"=>$exception->getMessage()]);
        }


    }

    /**
     * @Route("/file/get/{encode_string}", name="fileGet")
     */
    public function getFileAction($encode_string,UrlEncode $urlEncode,RedisService $redis,Request $request)
    {
        $redis=$redis->getRedisClient();
        $key=$redis->get($encode_string);
        $fileId=substr($urlEncode->Urldecrypt($key,$encode_string),0,stripos($urlEncode->Urldecrypt($key,$encode_string),'/'));
        $em = $this->getDoctrine()->getManager();
        $file=$em->getRepository('App:File')
            ->find($fileId);
        if($file==NULL)
            throw $this->createNotFoundException('The file does not exist');
        $downloadDir=$this->getParameter('apady_directory').'downloadFile/';
        if(!file_exists($downloadDir)){
            mkdir($downloadDir,0755);
        }
        $downloadFileDir=$downloadDir.$file->getFileName()."/";
        if(!file_exists($downloadFileDir)){
            mkdir($downloadFileDir,0755);
        }
        $targetdir=$file->getPath();
        try {
            $bfs = new FileSystem($this->getParameter('bfs_flag'));
            $bfs->get($targetdir, $downloadFileDir);
        }catch (IOExceptionInterface $exception){
            return new JsonResponse(["error"=>$exception->getMessage()]);
        }
        $response = new BinaryFileResponse ($downloadFileDir.$file->getFileName());
        $response->headers->set('Content-Type', 'text/plain');
        $response->setContentDisposition ( ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFileName());
        return $response;

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