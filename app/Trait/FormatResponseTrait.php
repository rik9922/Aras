<?php
namespace App\Trait;
use DB;
use Symfony\Component\HttpFoundation\Response;

trait FormatResponseTrait
{
    public function successResponse($message = '', $data = array(), $paginate = null){
        $response               = array();
        $response['status']     = "success";
        $response['message']    = $message;
        $response['error_type'] = '';
        $response['data']       = $data;
        if(!empty($paginate)){
            $response['total']       = $paginate->total();
            $response['totalPage']   = $paginate->lastPage();
            $response['currentPage'] = $paginate->currentPage();
            $response['perPage']     = $paginate->perPage();
        }
        return response()->json($response, Response::HTTP_OK);
    }

    public function errorResponse($message = '', $type = 'ERROR', $statusCode = Response::HTTP_BAD_REQUEST, $data = array()){
        [$type, $statusCode, $data] = $this->normalizeErrorResponseArgs($type, $statusCode, $data);

        $response               = array();
        $response['status']     = "failed";
        $response['message']    = $message;
        $response['error_type'] = (string) $type;
        $response['data']       = $data;
        return response()->json($response, (int) $statusCode);
    }

    /**
     * Normalize legacy misordered errorResponse() argument patterns.
     */
    private function normalizeErrorResponseArgs($type, $statusCode, $data): array
    {
        if (is_array($type)) {
            $statusCode = is_numeric($statusCode) ? (int) $statusCode : Response::HTTP_BAD_REQUEST;

            return ['ERROR', $statusCode, $data];
        }

        if (is_numeric($type)) {
            if (is_string($statusCode) && ! is_numeric($statusCode)) {
                $data = $data === [] ? ['detail' => $statusCode] : $data;
            } elseif (is_array($statusCode) || is_object($statusCode)) {
                $data = $statusCode;
            }

            return ['ERROR', (int) $type, $data];
        }

        return [$type, $statusCode, $data];
    }

    //upload Media function
    public function uploadMedia($media,$path){
        $image_ext = array('bmp','gif','ico','jpeg','jpg','png','svg','tif','tiff','webp');
        if($media){
            $extension =$media->extension();
            if(in_array($extension,$image_ext)){
                $clientSize = $media->getSize();
                $path = "/media/{$path}";
                $path = $media->store($path, ['disk' => 'imperium:files']);
            }else{
               return $this->errorResponse('Image extention not match', 'not_matched_error', 400);
            }
        }
        return $path;
    }

    //30-01-2024 for web
    public function uploadVideo($media,$path){
       $video_ext = array('mp4', 'MP4', 'M4P', 'M4V', 'WEBM', 'MPG', 'MP2', 'MPEG', 'MPE', 'MPV', 'OGG', 'M4P', 'M4V', 'SWF', 'AVCHD', 'mov', 'MOV');
        if($media){
            $extension =$media->extension();
            if(in_array($extension,$video_ext)){
                $clientSize = $media->getSize();
                $path = "/media/{$path}";
                $path = $media->store($path, ['disk' => 'imperium:files']);
            }else{
               return $this->errorResponse('Video extention does not match', 'not_matched_error', 400);
            }
        }
        return $path;
    }
}
