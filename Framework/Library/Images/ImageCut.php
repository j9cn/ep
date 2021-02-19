<?php
/**
 * Created by PhpStorm.
 * User: jh
 * Date: 2018/9/18
 * Time: 下午11:13
 */

namespace EP\Library\Images;


use EP\Exception\ELog;

class ImageCut
{
    /**
     * 图片类型
     * @var string
     */
    private $type;

    /**
     * 实际宽度
     * @var int
     */
    private $width;

    /**
     * 实际高度
     * @var int
     */
    private $height;

    /**
     * 原始图片
     * @var string
     */
    protected $src_images;

    /**
     * 原始图片信息
     * @var array|bool
     */
    protected $images_info;

    /**
     * 临时创建的图象
     * @var resource
     */
    private $resource;
    /**
     * 裁剪最大宽度
     * @var int
     */
    private $resize_width = 100;
    /**
     * 裁剪最大高度
     * @var int
     */
    private $resize_height = 100;


    function __construct($src_images)
    {
        $this->src_images = $src_images;
        $this->images_info = $this->getImageInfo($src_images);
        $this->type = $this->images_info['file_type'];

        //初始化图象
        $this->createImageResource();

        //目标图象地址
        $this->width = $this->images_info['width'];
        $this->height = $this->images_info['height'];
    }

    /**
     * 设置最大剪切大小
     *
     * @param $max_w
     * @param $max_h
     *
     * @return $this
     */
    function setCutSize($max_w, $max_h)
    {
        $size = $this->calcThumbSize($max_w, $max_h);
        $this->resize_width = $size['w'];
        $this->resize_height = $size['h'];

        return $this;
    }

    /**
     * 剪切图象
     *
     * @param array $coordinate
     * @param string $to_path
     * @param int $quality
     *
     * @return bool
     */
    function cut($coordinate, $to_path, $quality = 80)
    {
        if (!isset($coordinate['x'], $coordinate['y'], $coordinate['w'], $coordinate['h'])) {
            ELog::error('请设置剪切坐标x, y, w, h');
        }

        //创建缩略图
        if ($this->type != 'gif' && function_exists('imagecreatetruecolor')) {
            $thumb_images = imagecreatetruecolor($this->resize_width, $this->resize_height);
        } else {
            $thumb_images = imagecreate($this->resize_width, $this->resize_height);
        }
        imagealphablending($thumb_images, false);
        imagesavealpha($thumb_images, true);
        imagecopyresampled(
            $thumb_images,
            $this->resource,
            0,
            0,
            $coordinate['x'],
            $coordinate['y'],
            $this->resize_width,
            $this->resize_height,
            $this->width,
            $this->height
        );

        return $this->saveImage($thumb_images, $to_path, $this->images_info['file_type'], $quality);

    }

    /**
     *  计算裁剪缩略图大小
     *
     * @param $max_w //最大宽度
     * @param $max_h //最大高度
     *
     * @return array
     */
    protected function calcThumbSize($max_w, $max_h)
    {
        $w = $this->images_info['width'];
        $h = $this->images_info['height'];
        //计算缩放比例
        $w_ratio = $max_w / max(1, $w);
        $h_ratio = $max_h / max(1, $h);
        //计算裁剪图片宽、高度
        $thumb = array();
        if (($w <= $max_w) && ($h <= $max_h)) {
            $thumb['w'] = $w;
            $thumb['h'] = $h;
        } else {
            if (($w_ratio * $h) < $max_h) {
                $thumb['h'] = ceil($w_ratio * $h);
                $thumb['w'] = $max_w;
            } else {
                $thumb['w'] = ceil($h_ratio * $w);
                $thumb['h'] = $max_h;
            }
        }
        return $thumb;
    }

    /**
     * 获取图片详细信息
     *
     * @param $images
     *
     * @return array
     */
    protected function getImageInfo($images)
    {
        if (!is_file($images)) {
            ELog::error('文件不存在');
        }
        $image_info = getimagesize($images);
        if (false !== $image_info) {
            list($width, $height, $type) = $image_info;
            $image_ext = strtolower(image_type_to_extension($type));
            $image_type = substr($image_ext, 1);
            $image_size = filesize($images);

            $info = array(
                'width' => $width,
                'height' => $height,
                'ext' => $image_ext,
                'file_type' => $image_type,
                'size' => $image_size,
                'mime' => isset($image_info['mime']) ? $image_info['mime'] : '',
            );

            return $info;
        } else {
            ELog::error('不能裁剪非图片文件');
        }
    }

    /**
     * 创建临时图象
     */
    private function createImageResource()
    {
        switch ($this->type) {
            case 'jpg':
            case 'jpeg':
            case 'pjpeg':
                $this->resource = imagecreatefromjpeg($this->src_images);
                break;

            case 'gif':
                $this->resource = imagecreatefromgif($this->src_images);
                break;

            case 'png':
                $this->resource = imagecreatefrompng($this->src_images);
                break;

            case 'bmp':
                $this->resource = imagecreatefromwbmp($this->src_images);
                break;

            default:
                $this->resource = imagecreatefromgd2($this->src_images);
        }
        imagesavealpha($this->resource, true);
    }

    private function pngGif()
    {


    }

    /**
     * 存储图片
     *
     * @param $resource
     * @param $save_path
     * @param $image_type
     * @param int $quality
     *
     * @return bool
     */
    protected function saveImage($resource, $save_path, $image_type, $quality = 80)
    {
        switch ($image_type) {
            case 'jpg':
            case 'jpeg':
            case 'pjpeg':
                $ret = imagejpeg($resource, $save_path, $quality);
                break;

            case 'gif':
                $ret = imagegif($resource, $save_path);
                break;

            case 'png':
                $ret = imagepng($resource, $save_path);
                break;

            default:
                $ret = imagegd2($resource, $save_path);
                break;
        }

        return $ret;
    }
}