<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */
namespace App\Components;

/**
 *  Common features needed in a file.
 */
trait FileTrait
{
    private $userId;

    private $fileSize;

    private $mimeType;

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId=$userId;
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function setFileSize($fileSize)
    {
        $this->fileSize=$fileSize;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function setMimeType($mimeType)
    {
        $this->mimeType=$mimeType;
    }

}