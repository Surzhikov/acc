<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

use \Imagick;
use \ImagickDraw;
use \ImagickPixel;

class AiTalksImageService
{
	/**
	 * Draw picture for background of cover video
	 */
	public function drawCover($coverArtPath, $coverImagePath)
	{
	    $image = new Imagick();
	    $image->newImage(1080, 1920, new ImagickPixel('#18212D'));
	    $insertImage = new Imagick($coverArtPath);
	    //$insertImage->thumbnailImage(920, 920, true);
	    $insertImage->thumbnailImage(1080, 1080, true);
	    //$x = (1080 - $insertImage->getImageWidth()) / 2;
	    $x = 0;
	    //$y = 123; // Отступ сверху
	    $y = 0; // Отступ сверху
	    $image->compositeImage($insertImage, Imagick::COMPOSITE_OVER, $x, $y);
	    $image->writeImage($coverImagePath);
	    $image->clear();
	    $insertImage->clear();

	    return $coverImagePath;
	}

}