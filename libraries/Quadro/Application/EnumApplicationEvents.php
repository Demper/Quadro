<?php
declare(strict_types=1);

namespace Quadro\Application;

enum EnumApplicationEvents
{
    case BEFORE_DISPATCH;      // = 'Application:BeforeDispatch';
    case DISPATCHER_EXCEPTION; // = 'Application:DispatchException';
    case DISPATCHER_NO_MATCH;  // = 'Application:BeforeNoMatch';
    case BEFORE_SEND;          // = 'Application:BeforeSend';
}