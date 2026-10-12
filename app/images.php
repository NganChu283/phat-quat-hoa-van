<?php
function image_tag(string $path, string $alt, string $attributes='loading="lazy"', string $sizes='(max-width: 700px) 100vw, 50vw'): string {
    if (!image_path($path)) return '';
    $file=__DIR__.'/../public/'.$path;
    $dim=is_file($file)?@getimagesize($file):false;
    $stem=preg_replace('/\.[^.]+$/','',$path); $sources=[];
    foreach ([480,960,1600] as $width) {
        if (is_file(__DIR__.'/../public/'.$stem.'-'.$width.'.webp')) $sources[]=$stem.'-'.$width.'.webp '.$width.'w';
    }
    $src=is_file(__DIR__.'/../public/'.$stem.'.webp') ? $stem.'.webp' : $path;
    if ($sources && $dim && !in_array($dim[0],[480,960,1600],true)) $sources[]=$src.' '.$dim[0].'w';
    return '<img src="'.e($src).'" alt="'.e($alt).'" '.($dim?'width="'.$dim[0].'" height="'.$dim[1].'" ':'').'decoding="async" '.$attributes.($sources?' srcset="'.e(implode(', ',$sources)).'" sizes="'.e($sizes).'"':'').'>';
}
