<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */
namespace App\Event;
use App\Components\UploadFile;


class FileChunkArrivedEvent extends FileEvent
{
    private $chunkPath;
    private $mergeChunksIndex=array();

    public function __construct(UploadFile $file, $fileChunkPath)
    {
        parent::__construct($file);
        $this->chunkPath=$fileChunkPath;
    }

    public function getChunkPath(){
        return $this->chunkPath;
    }

    public function setMergeChunksIndex(array $index){
        $this->mergeChunksIndex=$index;
    }

    public function getMergeChunksIndex(){
        return $this->mergeChunksIndex;
    }

}