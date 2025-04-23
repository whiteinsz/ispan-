<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;


trait ImageUploadTrait {

    //上傳圖片
    public function uploadImage(Request $request, $inputName, $path)
    {
        if($request->hasFile($inputName)){

            $image = $request->{$inputName};
            // 取得副檔名
            $ext = $image->getClientOriginalExtension();
            $imageName = 'media_'.uniqid().'.'.$ext;
            // 將圖片移到 public/${path}
            $image->move(public_path($path), $imageName);
            // 回傳可存取的網址
           return $path.'/'.$imageName;
       }
    }

    // 一次上傳多張圖片
    public function uploadMultiImage(Request $request, $inputName, $path)
    {
        $imagePaths = [];
        
        if($request->hasFile($inputName)){

            $images = $request->{$inputName};

            foreach($images as $image){

                $ext = $image->getClientOriginalExtension();
                $imageName = 'media_'.uniqid().'.'.$ext;

                $image->move(public_path($path), $imageName);

                $imagePaths[] =  $path.'/'.$imageName;
            }

            return $imagePaths;
       }
    }

    // 更新圖片（刪除舊圖並上傳新圖）
    public function updateImage(Request $request, $inputName, $path, $oldPath=null)
    {
        if($request->hasFile($inputName)){
            // 如果存在舊圖片
            if(File::exists(public_path($oldPath))){
                File::delete(public_path($oldPath));
            }

            $image = $request->{$inputName};
            $ext = $image->getClientOriginalExtension();
            $imageName = 'media_'.uniqid().'.'.$ext;

            $image->move(public_path($path), $imageName);

           return $path.'/'.$imageName;
       }
    }

    /** Handle Delte File */
    public function deleteImage(string $path)
    {
        if(File::exists(public_path($path))){
            File::delete(public_path($path));
        }
    }

    // 
    public function updateImage2(Request $request, $inputName, $path, $oldPath = null, $width = 300, $height = 300)
    {
        if ($request->hasFile($inputName)) {

            // 檢查是否存在舊圖片，並移動到 temp 資料夾
            if ($oldPath && File::exists(public_path($oldPath))) {
                // 目標資料夾：檢查是否存在 'temp' 資料夾，如果不存在則創建
                $tempPath = public_path($path . '/temp');
                if (!File::exists($tempPath)) {
                    File::makeDirectory($tempPath, 0777, true); // 如果 temp 資料夾不存在則創建
                }

                // 生成舊圖片的新路徑
                $oldImageName = basename($oldPath);
                $newOldImagePath = $tempPath . '/' . $oldImageName;

                // 移動舊圖片到 temp 資料夾
                File::move(public_path($oldPath), $newOldImagePath);
            }

            // 檢查目標資料夾是否存在，若不存在則創建
            if (!File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), 0777, true); // 若資料夾不存在則創建
            }

            // 上傳圖片
            $image = $request->file($inputName);
            $ext = $image->getClientOriginalExtension();
            $imageName = 'media_' . uniqid() . '.' . $ext;
            $imagePath = public_path($path . '/' . $imageName);

            // 使用 Intervention Image 來縮放圖片
            Image::read($image)
                ->scale($width, $height, function ($constraint) {
                    $constraint->aspectRatio(); // 保持原比例
                    $constraint->upsize(); // 避免放大圖片
                })
                ->save($imagePath); // 儲存圖片
            return $path . '/' . $imageName; // 回傳圖片存放路徑
        }

        return $oldPath; // 如果沒有上傳新圖片，回傳舊的路徑
    }
}

