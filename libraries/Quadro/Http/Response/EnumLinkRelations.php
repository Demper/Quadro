<?php
declare(strict_types=1);

namespace Quadro\Http\Response;

enum EnumLinkRelations: string
{
    case Alternate = 'alternate';
    case Author = 'author';
    case Next = 'next';
    case Prev = 'prev';
    case DnsPrefetch = 'dns-prefetch';
    case Help = 'help';
    case Icon = 'icon';
    case License = 'license';
    case PingBack = 'pingback';
    case PreConnect = 'preconnect';
    case Prefetch = 'prefetch';
    case Preload = 'preload';
    case Prerender = 'prerender';
    case Search = 'search';
    case Stylesheet = 'stylesheet';

    // custom
    case First = 'first';
    case Last = 'last';

}