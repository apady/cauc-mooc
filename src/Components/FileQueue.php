<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */

namespace App\Components;


class FileQueue implements \ArrayAccess,\IteratorAggregate,\Countable
{
    /**
     * @var UploadFile[]
     */
    private $files=array();

    /**
     * Add a new file into the file queue.
     * @param $key
     * @param UploadFile $file
     */
    public function addFile($key,UploadFile $file)
    {
        $this->files[$key]=$file;
    }

    /**
     * @param $key
     * @return UploadFile
     */
    public function getFile($key)
    {
        return $this->files[$key];
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasFile($key)
    {
        return array_key_exists($key,$this->files);
    }

    /**
     * @param $key
     */
    public function removeFile($key)
    {
        if($this->hasFile($key))
        {
            unset($this->files[$key]);
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->hasFile($offset);
    }

    /**
     * @param mixed $offset
     * @return UploadFile|mixed
     */
    public function offsetGet($offset)
    {
        return $this->getFile($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $file
     */
    public function offsetSet($offset, $file)
    {
        return $this->addFile($offset,$file);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        return $this->removeFile($offset);
    }

    /**
     * IteratorAggregate for iterating over the object like an array.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
       return new \ArrayIterator($this->files);
    }

    public function count()
    {
        return \count($this->files);
    }


}