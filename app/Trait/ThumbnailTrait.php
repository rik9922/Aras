<?php

namespace App\Trait;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait ThumbnailTrait
{
    // public function createThumbnail($imagePath, $thumbWidth = 120)
    // {
    //     try {
    //         ini_set('memory_limit', '256M');
    //         // Set the paths
    //         $sourceImagePath = public_path($imagePath);
    //         $thumbnailDir = public_path('media/thumbnail');

    //         // Ensure the thumbnail directory exists
    //         if (!File::isDirectory($thumbnailDir)) {
    //             File::makeDirectory($thumbnailDir, 0755, true);
    //         }

    //         // Extract filename and create thumbnail path
    //         $filename = basename($imagePath);
    //         $thumbnailPath = $thumbnailDir . '/' . $filename;

    //         // Get the image type
    //         $imageType = exif_imagetype($sourceImagePath);
    //         // Load the image based on its type
    //         switch ($imageType) {
    //             case IMAGETYPE_JPEG:
    //                 // dd($imageType);
    //                 Log::error('Thumbnail creation failed: ' . $sourceImagePath);
    //                 $sourceImage = imagecreatefromjpeg($sourceImagePath);
    //                 break;
    //             case IMAGETYPE_PNG:
    //                 $sourceImage = @imagecreatefrompng($sourceImagePath);
    //                 break;
    //             default:
    //                 throw new \Exception('Unsupported image type');
    //         }

    //         if (!$sourceImage) {
    //             throw new \Exception('Failed to load image: ' . $sourceImagePath);
    //         }

    //         // Get original dimensions
    //         $width = imagesx($sourceImage);
    //         $height = imagesy($sourceImage);

    //         // Calculate thumbnail dimensions
    //         $thumbHeight = floor($height * ($thumbWidth / $width));

    //         // Create a new image
    //         $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

    //         // Preserve transparency for PNG images
    //         if ($imageType == IMAGETYPE_PNG) {
    //             imagealphablending($thumbnail, false);
    //             imagesavealpha($thumbnail, true);
    //             $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
    //             imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
    //         }

    //         // Resize the image
    //         imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

    //         // Save the thumbnail
    //         switch ($imageType) {
    //             case IMAGETYPE_JPEG:
    //                 imagejpeg($thumbnail, $thumbnailPath);
    //                 break;
    //             case IMAGETYPE_PNG:
    //                 imagepng($thumbnail, $thumbnailPath);
    //                 break;
    //         }

    //         // Free up memory
    //         imagedestroy($sourceImage);
    //         imagedestroy($thumbnail);

    //         // Return the relative path to the thumbnail
    //         return '/media/thumbnail/' . $filename;
    //     } catch (\Exception $e) {
    //         Log::error('Thumbnail creation failed: ' . $e->getMessage());
    //         return false;
    //     }
    // }



    public function createThumbnail($imagePath, $thumbWidth = 120)
    {
        try {
            ini_set('memory_limit', '256M');
            // Correct thumbnail directory path
            $thumbnailDir = 'media/thumbnail';
            $filename = basename($imagePath);
            $thumbnailPath = $thumbnailDir . '/' . $filename;

            // Get the source image from S3
            $sourceImageContent = Storage::disk('s3')->get($imagePath);
            $tempSourcePath = tempnam(sys_get_temp_dir(), 'source_');

            // Save source image to a temporary file
            file_put_contents($tempSourcePath, $sourceImageContent);

            // Get image type using getimagesizefromstring
            $imageInfo = getimagesizefromstring($sourceImageContent);
            if ($imageInfo === false) {
                throw new \Exception('Failed to read image information.');
            }
            $imageType = $imageInfo[2];

            // Load the image based on its type
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($tempSourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = @imagecreatefrompng($tempSourcePath);
                    break;
                default:
                    throw new \Exception('Unsupported image type');
            }

            if (!$sourceImage) {
                throw new \Exception('Failed to load image from S3: ' . $imagePath);
            }

            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            $thumbHeight = floor($height * ($thumbWidth / $width));

            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

            if ($imageType == IMAGETYPE_PNG) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
            }

            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            $tempThumbPath = tempnam(sys_get_temp_dir(), 'thumb_');
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    imagejpeg($thumbnail, $tempThumbPath);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($thumbnail, $tempThumbPath);
                    break;
            }

            if (!file_exists($tempThumbPath)) {
                Log::error('Temporary thumbnail creation failed');
                return false;
            }

            // Upload the thumbnail to S3
            $filename = basename($thumbnailPath);
            $uploaded = Storage::disk('s3')->putFileAs(dirname($thumbnailPath), $tempThumbPath, $filename);

            // $uploaded = Storage::disk('s3')->put($thumbnailPath, file_get_contents($tempThumbPath), 'public');
            Log::info('S3 Upload Attempt', [
                'thumbnailPath' => $thumbnailPath,
                'uploaded' => $uploaded
            ]);

            imagedestroy($sourceImage);
            imagedestroy($thumbnail);
            @unlink($tempSourcePath);
            @unlink($tempThumbPath);

            if ($uploaded) {
                return $thumbnailPath;
            } else {
                throw new \Exception('Failed to upload thumbnail to S3');
            }
        } catch (\Exception $e) {
            Log::error('Thumbnail creation failed: ' . $e->getMessage());
            return false;
        }
    }


    public function createThumbnailForGallery($imageUrl, $thumbWidth = 300)
    {
        try {
            ini_set('memory_limit', '350M');

            // Parse the image path from the URL
            $parsedUrl = parse_url($imageUrl);
            $relativeImagePath = ltrim($parsedUrl['path'], '/'); // Remove leading slash

            // Extract the directory and filename from the image path
            $directory = dirname($relativeImagePath); // e.g., media/gallery/Playing Catch
            $filename = basename($relativeImagePath); // e.g., 1730102716.man-with-laptop-dark.png

            // Create the thumbnail directory path by replacing 'gallery' with 'galleryThumbnail'
            $thumbnailDir = str_replace('gallery', 'galleryThumbnail', $directory);

            // Fetch the source image from S3
            $sourceImageContent = Storage::disk('s3')->get($relativeImagePath);
            $tempSourcePath = tempnam(sys_get_temp_dir(), 'source_');

            // Save source image to a temporary file
            file_put_contents($tempSourcePath, $sourceImageContent);

            // Get image type from the source content
            $imageInfo = getimagesizefromstring($sourceImageContent);
            if ($imageInfo === false) {
                throw new \Exception('Failed to read image information.');
            }
            $imageType = $imageInfo[2];

            // Load the image based on its type
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($tempSourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = @imagecreatefrompng($tempSourcePath);
                    break;
                default:
                    throw new \Exception('Unsupported image type');
            }

            if (!$sourceImage) {
                throw new \Exception('Failed to load image from S3: ' . $relativeImagePath);
            }

            // Get original dimensions
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);

            // Calculate thumbnail dimensions
            $thumbHeight = floor($height * ($thumbWidth / $width));

            // Create a new image
            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

            // Preserve transparency for PNG images
            if ($imageType == IMAGETYPE_PNG) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
            }

            // Resize the image
            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            // Save the thumbnail to a temporary file
            $tempThumbPath = tempnam(sys_get_temp_dir(), 'thumb_');
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    imagejpeg($thumbnail, $tempThumbPath);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($thumbnail, $tempThumbPath);
                    break;
            }

            // Upload the thumbnail to S3 using putFileAs
            $uploaded = Storage::disk('s3')->putFileAs($thumbnailDir, new \Illuminate\Http\File($tempThumbPath), $filename);

            Log::info('S3 Upload Attempt', [
                'thumbnailPath' => $thumbnailDir . '/' . $filename,
                'uploaded' => $uploaded
            ]);

            // Free up memory
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);
            @unlink($tempSourcePath);
            @unlink($tempThumbPath);

            // Return the relative path to the thumbnail on S3
            if ($uploaded) {
                return '/' . $thumbnailDir . '/' . $filename; // Return the relative path
            } else {
                throw new \Exception('Failed to upload thumbnail to S3');
            }
        } catch (\Exception $e) {
            Log::error('Thumbnail creation failed: ' . $e->getMessage());
            return false;
        }
    }

}
