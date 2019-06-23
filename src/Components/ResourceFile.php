<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */
namespace App\Components;

/**
 * Files for course resource.
 */

class ResourceFile extends UploadFile
{

    private $courseId;


    public function getCourseId(){
        return $this->courseId;
    }

    public function setCourseID($courseId)
    {
        $this->courseId=$courseId;
    }
}