<?php
function site_url(string $path = ''): string {
    $base = rtrim(config()['site_url'] ?? '', '/');
    if ($base === '') {
        // Local preview fallback. Set site_url to the public HTTPS URL in production.
        $base='http://localhost'.rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    return $base.'/'.ltrim($path,'/');
}
function json_ld(array $data): void {
    echo '<script type="application/ld+json">'.json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
}
function seo(string $title, string $description, string $path, string $image='assets/photos/chang-son-fans.webp', string $type='website'): void {
    echo '<title>'.e($title).'</title><meta name="description" content="'.e($description).'">';
    echo '<link rel="canonical" href="'.e(site_url($path)).'">';
    foreach (['og:locale'=>'vi_VN','og:site_name'=>'Phất Quạt Họa Văn','og:type'=>$type,'og:title'=>$title,'og:description'=>$description,'og:url'=>site_url($path),'og:image'=>site_url($image),'og:image:alt'=>$title] as $key=>$value) echo '<meta property="'.e($key).'" content="'.e($value).'">';
    echo '<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="'.e($title).'"><meta name="twitter:description" content="'.e($description).'"><meta name="twitter:image" content="'.e(site_url($image)).'">';
}
