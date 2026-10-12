<?php
function sanitize_rich_text(string $html): string {
    if (trim($html)==='') return '';
    $dom=new DOMDocument('1.0','UTF-8'); $prior=libxml_use_internal_errors(true);
    try { $dom->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING); }
    finally { libxml_clear_errors(); libxml_use_internal_errors($prior); }
    $allowed=['p','br','strong','b','em','i','u','h2','h3','ul','ol','li','blockquote','a'];
    $blocked=['script','style','iframe','object','embed','svg','math','form','input','button','textarea','select','template'];
    $render=function(DOMNode $node) use (&$render,$allowed,$blocked): string {
        if ($node instanceof DOMText) return e($node->nodeValue);
        if (!($node instanceof DOMElement)) return '';
        $tag=strtolower($node->tagName); if (in_array($tag,$blocked,true)) return '';
        $inner=''; foreach ($node->childNodes as $child) $inner.=$render($child);
        if ($tag==='div') $tag='p';
        if (!in_array($tag,$allowed,true)) return $inner;
        if ($tag==='br') return '<br>';
        $attrs='';
        if ($tag==='a') {
            $href=trim($node->getAttribute('href'));
            if (!preg_match('~^(?:https?://|mailto:|/(?!/)|#)~i',$href) || preg_match('/[\x00-\x20\x7f]/',$href)) return $inner;
            $attrs=' href="'.e($href).'" rel="noopener noreferrer"';
        }
        return '<'.$tag.$attrs.'>'.$inner.'</'.$tag.'>';
    };
    $out=''; $body=$dom->getElementsByTagName('body')->item(0);
    if ($body) foreach ($body->childNodes as $child) $out.=$render($child);
    return trim($out);
}
function rich_text_has_content(string $html): bool {
    return trim(str_replace("\u{00a0}",' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8')))!=='';
}
function post_body(array $post): string {
    if (($post['body_format'] ?? 'plain')==='html') return sanitize_rich_text($post['body']);
    return '<p>'.nl2br(e($post['body'])).'</p>';
}
