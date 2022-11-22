<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Exception;
use Symfony\Component\HttpFoundation\File\File;

class FileHelper
{
    public static function saveBase64($base64File, $dir = "files")
    {
        $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64File));

        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
        file_put_contents($tmpFilePath, $fileData);

        $tmpFile = new File($tmpFilePath);

        $file = new UploadedFile(
            $tmpFile->getPathname(),
            $tmpFile->getFilename(),
            $tmpFile->getMimeType(),
            0,
            true
        );
        $filename = 'images/' . $dir . '/' . date('YmdHi') . '.' . $file->extension();
        if ($file->move(public_path('images/' . $dir), $filename)) {

            return $filename;
        }
        return null;
    }

    public static function fileUpload($data)
    {

        if (isset($data["file"])) {
            $file = $data["file"];
            $validator = Validator::make($data->all(), [
                ['file' => ['mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xlsx,csv,mp4,mp3,mov,wmv,avi|size:2048'],]
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    "errors" => $validator->errors()
                ])->header('Status-Code', 200);
            }
            $location= $data["location"]?$data["location"]."/":"";
            try {
                $dir = "files";
                $mime = explode("/", $file->getMimeType());
                if ($mime[0]== "image") {
                    $dir = "images";
                } elseif ($mime[0] == "video") {
                    $dir = "videos";
                }
                $dirPath = $dir . '/'.$location;
                $filename = $dirPath . date('YmdHi') . '.' . $file->extension();
                $file->move(public_path($dirPath), $filename);

                return $filename;
            }catch (Exception $e){
                return response()->json([
                    'success' => false,
                    "errors" => $validator->errors()
                ])->header('Status-Code', 200);
            }

        }

    }
}
