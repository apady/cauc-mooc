<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */
namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\FileUploadEvents;
use App\Event\FileChunkArrivedEvent;
use App\Event\FileMergeEvent;
use App\Event\FileMergeCompletedEvent;

class FileUploadListener implements EventSubscriberInterface
{
    /**
     * Called when a file chunk arrived.
     */
    public function onChunkArrived(FileChunkArrivedEvent $event){
        /**
         * check if the chunk can be merge in to a file.
         */
        //do something....
        $event->getFile()->setIsCanBeMergeStatus(true);
        $event->setMergeChunksIndex([1,2,3]);


    }

    /**
     * Called when some chunks of a file can be merge.
     */
    public function onMerge(FileMergeEvent $event){

        $event->getFile()->setIsCanBeMergeStatus(false);

        $chunksIndex=$event->getMergeChunksIndex();
        //do something....

        $event->getFile()->setIsMergeCompleted(true);


    }
    /**
     * Called when all of file chunks arrived and the file was completely merged.
     * @throws ORMException
     */
    public function onMergeCompleted(FileMergeCompletedEvent $event)
    {
        /**
         * Check file integrity and persist the file into the database.
         */

        // do something

        //$event->getFile()->setIsUploadFinished(true);

    }
    public static function getSubscribedEvents(){
        return [
            FileUploadEvents::CHUNK_ARRIVED=>['onChunkArrived',32],
            FileUploadEvents::MERGE=>['onMerge',16],
            FileUploadEvents::MERGE_COMPLETED=>['onMergeCompleted'],

        ];
    }
}