<?php

namespace App;

use Illuminate\Http\UploadedFile as HttpUploadedFile;

trait UploadFileTrait
{
   public function upload(HttpUploadedFile $file,$folder,$disk)
    {
        return $file->store($folder,$disk);
    }
}
