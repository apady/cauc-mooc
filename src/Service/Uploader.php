<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */

namespace App\Service;
use App\Components\ResourceFile;
use App\Entity\File;
use App\Event\FileMergeCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Components\UploadFile;
use App\Components\FileQueue;
use App\Event\FileChunkArrivedEvent;
use App\Event\FileMergeEvent;
use App\FileUploadEvents;
use BFS\Exception\IOExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use BFS\FileSystem;
use App\Service\RedisService;


class Uploader implements UploaderInterface
{
    protected $dispatcher;
    protected $fileQueue;
    protected $entityManager;
    protected $redis;
    protected $params;

    public function __construct(EventDispatcherInterface $dispatcher,EntityManagerInterface $entityManager,FileQueue $fileQueue=null,RedisService $redis,ParameterBagInterface $params)
    {
        $this->dispatcher=$dispatcher;
        $this->fileQueue=$fileQueue?:new FileQueue();
        $this->entityManager=$entityManager;
        $this->redis=$redis;
        $this->params=$params;
    }

    /**
     * @param UploadFile $file
     * @param string $fileChunkPath
     * @return string
     */
    public function handle(UploadFile $file, $fileChunkPath)
    {
        $event=new FileChunkArrivedEvent($file,$fileChunkPath);
        if(!$this->fileQueue->hasFile($file->getFilePath()))
            $this->fileQueue->addFile($file->getFilePath(),$file);

        $this->dispatcher->dispatch(FileUploadEvents::CHUNK_ARRIVED,$event);

        if($event->getFile()->isCanBeMerged())
        {
            $mergeEvent=new FileMergeEvent($event->getFile(),$event->getMergeChunksIndex(),$fileChunkPath);
            $this->dispatcher->dispatch(FileUploadEvents::MERGE,$mergeEvent);
        }
        if($event->getFile()->isMergeCompleted())
        {
            $mergeCompletedEvent=new FileMergeCompletedEvent($file);
            $this->dispatcher->dispatch(FileUploadEvents::MERGE_COMPLETED,$mergeCompletedEvent);
            if($file->isUploadFinished()) {
                $this->finishUpload($file);
                //     $this->recordFileMd5($file);
            }
        }
        return $file->isUploadFinished()?'UploadFinished':'Uploading';
    }

    /**
     * Add a chunk digest.
     * @param string $filePath The absolute file path.
     * @param string $fileChunkPath The absolute chunk path.
     * @param string $digest
     * @return bool
     */
    public function addFileChunkDigest($filePath,$fileChunkPath,$digest)
    {
        if($this->fileQueue->hasFile($filePath))
        {
            $this->fileQueue->getFile($filePath)->setChunksDigest($fileChunkPath,$digest);
            return true;
        }
        return false;
    }

    public function recordFileMd5(ResourceFile $uploaderFile)
    {
        if($uploaderFile->getChunk()!=-1&&$uploaderFile->getChunk()==$uploaderFile->getChunks()-1){
            $md5Dir=$this->params->get('apady_directory').$uploaderFile->getUserId().'/';
            if(!file_exists($md5Dir)){
                mkdir($md5Dir,0755,true);
            }
            if(!file_exists($md5Dir.$uploaderFile->getFileMd5())){
                $handle=@fopen($md5Dir.$uploaderFile->getFileMd5(),"w");
                fwrite($handle,$uploaderFile->getFilePath());
            }
        }
    }
    /**
     * Called when upload finished.
     * @param ResourceFile $file
     * @return bool
     */
    public function finishUpload(ResourceFile $uploaderFile)
    {
        $this->fileQueue->removeFile($uploaderFile->getFilePath());
        if($uploaderFile->getChunk()==$uploaderFile->getChunks()-1||$uploaderFile->getChunks()==null) {
            $course = $this->entityManager->getRepository('App:Courses')
                ->find($uploaderFile->getCourseId());
            if ($course == NULL) {
                return false;
            }
            $user = $this->entityManager->getRepository('App:User')
                ->find($uploaderFile->getUserId());
            $file = new File();
            $file->setUser($user);
            $file->setMimeType($uploaderFile->getMimeType());
            $file->setSize($uploaderFile->getFileSize());
            $file->setFileName($uploaderFile->getFileName());
            $file->setPath('/'.$user->getUsername().$user->getId().'/'.$uploaderFile->getCourseId().'/教学资源/'.$uploaderFile->getFileName().'/');
            $this->entityManager->persist($file);
            $course->addResourceFile($file);
            $this->entityManager->flush();

            $this->redis->getRedisClient()->del($uploaderFile->getUserId().$uploaderFile->getFileName());
            return true;
        }
        return true;
    }
}