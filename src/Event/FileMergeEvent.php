<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */

namespace App\Event;


use App\Components\UploadFile;

class FileMergeEvent extends FileEvent
{
    private $mergeChunksIndex=array();

    public function __construct(UploadFile $file,array $mergeChunksIndex)
    {
        parent::__construct($file);
        $this->setMergeChunksIndex($mergeChunksIndex);
    }

    public function setMergeChunksIndex(array $index){
        $this->mergeChunksIndex=$index;
    }

    public function getMergeChunksIndex(){
        return $this->mergeChunksIndex;
    }


}