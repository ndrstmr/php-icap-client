<?php
declare(strict_types=1);

namespace Ndrstmr\Icap;

/**
 * Enumeration of supported ICAP methods.
 */
enum IcapMethod: string
{
    case OPTIONS = 'OPTIONS';
    case RESPMOD = 'RESPMOD';
    case REQMOD = 'REQMOD';
}
