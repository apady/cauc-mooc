<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */

namespace App\Service;

use App\Components\UploadFile;


interface UploaderInterface
{
    public function handle(UploadFile $file,$fileChunkPath);

}