<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */

namespace App\Service;
use App\Entity\File;
use App\Event\FileMergeCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Components\UploadFile;
use App\Components\FileQueue;
use App\Event\FileChunkArrivedEvent;
use App\Event\FileMergeEvent;
use App\FileUploadEvents;

class Uploader implements UploaderInterface
{
    protected $dispatcher;
    protected $fileQueue;
    protected $entityManager;
    protected $parameterBag;

    public function __construct(EventDispatcherInterface $dispatcher,EntityManagerInterface $entityManager,ParameterBagInterface $bag)
    {
        $this->dispatcher=$dispatcher;
        $this->fileQueue=new FileQueue();
        $this->entityManager=$entityManager;
        $this->parameterBag=$bag;
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
            $mergeEvent=new FileMergeEvent($event->getFile(),$event->getMergeChunksIndex());
            $this->dispatcher->dispatch(FileUploadEvents::MERGE,$mergeEvent);

        }
        if($event->getFile()->isMergeCompleted())
        {
            $mergeCompletedEvent=new FileMergeCompletedEvent($file);
            $this->dispatcher->dispatch(FileUploadEvents::MERGE_COMPLETED,$mergeCompletedEvent);
            if($file->isUploadFinished())
                $this->finishUpload($file);
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

    /**
     * Called when upload finished.
     * @param UploadFile $uploadFile
     */
    private function finishUpload(UploadFile $uploadFile)
    {
        $this->fileQueue->removeFile($uploadFile->getFilePath());
//        $file=new File();
//        $file->setFileName($uploadFile->getFileName());
//        $file->setPath($uploadFile->getFilePath());
//        $this->entityManager->persist($file);
//        $this->entityManager->flush();

    }
}