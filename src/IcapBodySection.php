<?php
declare(strict_types=1);

namespace Ndrstmr\Icap;

/**
 * Enumeration of supported encapsulated body section identifiers.
 */
enum IcapBodySection: string
{
    case REQ_HDR  = 'req-hdr';
    case RES_HDR  = 'res-hdr';
    case REQ_BODY = 'req-body';
    case RES_BODY = 'res-body';
    case NULL_BODY = 'null-body';
}
