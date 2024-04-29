<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PanAndZoom;

class TestPanZoom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-pan-zoom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        $imageW = 3360;
        $imageH = 1920;
        $imageRatio = $imageW / $imageH;

        $videoWidth = 1080;
        $videoHeight = 1920;
        $videoRatio = $videoWidth / $videoHeight;

        if ($imageRatio > $videoRatio) {
            $padH = $imageW / $videoRatio;
            $padW = $imageW;
        } else {
            $padW = $imageH * $videoRatio;
            $padH = $imageH;
        }


        //exit;
   

        $image = storage_path('app/pantest.png');
        $panAndZoom = new PanAndZoom;
        $video = storage_path('app/pantestZoom.mov'); 
        $panAndZoom->setPhotoPath($image);
        $panAndZoom->setOutputVideoPath($video);
        $panAndZoom->setFps(25);
        $panAndZoom->setVideoDimensions(1080, 1920);
        $panAndZoom->setTime(3.9183895704042);
        $panAndZoom->setFromBox(535, 48, 521, 926);
        $panAndZoom->setToBox(910, 258, 336, 597);
        $panAndZoom->createVideo();
exit;

        $image = storage_path('app/pantest.png');
        $video = storage_path('app/pantestZoomIn.mov'); 
        $panAndZoom = new PanAndZoom;
        $panAndZoom->setPhotoPath($image);
        $panAndZoom->setOutputVideoPath($video);
        $panAndZoom->setFps(25);
        $panAndZoom->setVideoDimensions(1080, 1920);
        $panAndZoom->setTime(3);
        $panAndZoom->setFromBox(1130, 192, 365, 649);
        $panAndZoom->setToBox(391, 529, 176, 313);
        $panAndZoom->createVideo();


    }
}
