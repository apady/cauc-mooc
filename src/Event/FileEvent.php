<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */

namespace App\Event;
use Symfony\Component\EventDispatcher\Event;
use App\Components\UploadFile;

class FileEvent extends Event
{

    private $file;

    public function __construct(UploadFile $file)
    {
        $this->file=$file;
    }

    /**
     * Get absolute path of the uploading file.
     * @return string
     */
    public function getFilePath()
    {
        return $this->file->getFilePath();
    }

    /**
     * Get UploadFile object.
     * @return UploadFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     *  Get  absolute path array of all chunks.
     * @return array
     */
    public function getChunks()
    {
        return $this->file->getChunks();
    }





}