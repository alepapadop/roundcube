<?php




$myImage = ImageCreate(100,50);

$white = ImageColorAllocate($myImage, 255,255,255);
$black = ImageColorAllocate($myImage, 0,0,0);

$font = imageloadfont('festival.gdf');

ImageString($myImage,$font,0,0,$text,$black);



header("Content-type: image/png");
ImagePng($myImage);

ImageDestroy($myImage);



?> 
