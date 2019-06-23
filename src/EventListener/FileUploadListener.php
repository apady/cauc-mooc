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
use App\Service\RedisService;
use BFS\FileSystem;



class FileUploadListener implements EventSubscriberInterface
{
    private $redis;

    public function __construct(RedisService $redis)
    {
        $this->redis=$redis;
    }
    /**
     * Called when a file chunk arrived.
     */
    public function onChunkArrived(FileChunkArrivedEvent $event){
        /**
         * check if the chunk can be merge in to a file.
         */
        if(!file_exists($event->getFile()->getFilePath()))
            mkdir($event->getFile()->getFilePath(),0755,true);
        $event->getFile()->setIsCanBeMergeStatus(true);

//        if($event->getFile()->getChunk()==0){
//            $chunk_array=$event->getChunks();
//            for ($i=0;$i<$event->getFile()->getChunks();$i++){
//                $chunk_array[$i]=0;
//            }
//            $chunk_array[0]=1;
//              $event->getFile()->setMergerChunk(0);
//        }else{
//            $chunk_array[$event->getFile()->getChunk()]=1;
//        }
    }
//    public function Search(FileMergeEvent $event)
//    {
//        $chunk_array=$event->getChunks();
//        $chunk_index=$event->getMergeChunksIndex();
//        for($i=$event->getFile()->getMergerChunk();$i<$event->getFile()->getChunks();$i++){
//            if($chunk_array[$i]==1){
//                array_push($chunk_index,$i);
//            }else{
//                $event->getFile()->setMergerChunk($i);
//                break;
//            }
//        }
//
//    }
    /**
     * Called when some chunks of a file can be merge.
     */
    public function onMerge(FileMergeEvent $event){
        //       Search($event);

        $event->getFile()->setIsCanBeMergeStatus(false);

        $in=fopen($event->getChunkPath(),'r');

        if($event->getFile()->getChunk()==0){
            $out=fopen($event->getFile()->getFilePath().'0','w');
        }else if($event->getFile()->getChunk()==-1){
            $out=fopen($event->getFile()->getFilePath().$event->getFile()->getFileName(),'w');
        }else if($event->getFile()->getChunk()==$event->getFile()->getChunks()-1){
            $bfs_in=fopen($event->getFile()->getFilePath().($event->getFile()->getChunk()-1),'r');
            $out=fopen($event->getFile()->getFilePath().$event->getFile()->getFileName(),'w');
        }else {
            $bfs_in=fopen($event->getFile()->getFilePath().($event->getFile()->getChunk()-1),'r');
            $out=fopen($event->getFilePath().$event->getFile()->getChunk(),'w');
        }

        if ($event->getFile()->getChunk()>0){
            while(!feof($bfs_in)){
                fwrite($out,fread($bfs_in,4096));
            }
            while(!feof($in)){
                fwrite($out,fread($in,4096));
            }
            fclose($bfs_in);
            @unlink($event->getFile()->getFilePath().($event->getFile()->getChunk()-1));
        }else{
            while(!feof($in)){
                fwrite($out,fread($in,4096));
            }
        }
        fclose($in);
        fclose($out);

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
        $event->getFile()->setIsMergeCompleted(false);
        if($event->getFile()->getChunk()>0)
            $this->redis->getRedisClient()->sadd($event->getFile()->getUserId().$event->getFile()->getFileName(),$event->getFile()->getChunk());
        else if($event->getFile()->getChunk()==0){
            $this->redis->getRedisClient()->sadd($event->getFile()->getUserId().$event->getFile()->getFileName(),$event->getFile()->getChunk());
            $this->redis->getRedisClient()->expire($event->getFile()->getUserId().$event->getFile()->getFileName(),6*60*60);
        }
        $event->getFile()->setIsUploadFinished(true);
    }
    public static function getSubscribedEvents(){
        return [
            FileUploadEvents::CHUNK_ARRIVED=>['onChunkArrived',32],
            FileUploadEvents::MERGE=>['onMerge',16],
            FileUploadEvents::MERGE_COMPLETED=>['onMergeCompleted'],
        ];
    }
}