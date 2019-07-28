<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */

namespace App\Components;


class UploadFile
{
    use FileTrait;
    /**
     * @var string
     * The absolute path of a file.
     * eg. /home/demoFile.txt
     */
    private $filePath;

    /**
     * @var string
     */
    private $fileName;
    /**
     * @var array
     * The absolute path array of all chunks .
     * eg. ['/home/demoFile.txt_chunk_1','/home/demoFile.txt_chunk_2',...]
     */
    private $fileChunks=array();
    /**
     * @var array
     * The digests of all chunks.
     * These digests can be calculate via many digest algorithm,like MD5,SHA256,etc.
     */
    private $chunksDigest=array();
    /**
     * @var bool
     * Become true when file chunks satisfy merge condition.
     */
    private $isCanBeMerged;

    /**
     * @var bool
     * Become true when all of the file chunks are successfully merged.
     */
    private $isMergeCompleted;

    private $isUploadFinished;


    public function __construct($filePath)
    {
        $this->filePath=$filePath;
        $this->isCanBeMerged=false;
        $this->isMergeCompleted=false;
        $this->isUploadFinished=false;
        $this->fileName=substr($filePath,strrpos($filePath,"/")+1);
        $this->fileChunks=\array_diff(scandir(substr($filePath,0,strrpos($filePath,"/"))),['.','..']);
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getChunks()
    {
        return $this->fileChunks;
    }

    public function getChunksDigest()
    {
        return $this->chunksDigest;
    }

    public function setChunksDigest($chunkPath,$digest)
    {
        $this->chunksDigest[$chunkPath]=$digest;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }
    public function setIsCanBeMergeStatus($status)
    {
        $this->isCanBeMerged=$status;
    }

    public function setIsMergeCompleted($status)
    {
        $this->isMergeCompleted=$status;
    }

    public function isCanBeMerged()
    {
        return $this->isCanBeMerged;
    }

    public function isMergeCompleted()
    {
        return $this->isMergeCompleted;
    }

    public function setIsUploadFinished($status)
    {
        $this->isUploadFinished=$status;
    }

    public function isUploadFinished()
    {
        return $this->isUploadFinished;
    }

}