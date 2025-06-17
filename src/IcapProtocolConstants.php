<?php
declare(strict_types=1);

namespace Ndrstmr\Icap;

/**
 * Defines constants related to the ICAP protocol.
 */
final class IcapProtocolConstants
{
    public const PROTOCOL_PREFIX = 'ICAP/';
    public const PROTOCOL_VERSION = '1.0';

    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_RESPMOD = 'RESPMOD';
    public const METHOD_REQMOD = 'REQMOD';

    public const HEADER_HOST = 'Host';
    public const HEADER_USER_AGENT = 'User-Agent';
    public const HEADER_CONNECTION = 'Connection';
    public const HEADER_ENCAPSULATED = 'Encapsulated';

    public const SECTION_REQ_HDR  = 'req-hdr';
    public const SECTION_RES_HDR  = 'res-hdr';
    public const SECTION_REQ_BODY = 'req-body';
    public const SECTION_RES_BODY = 'res-body';
    public const SECTION_NULL_BODY = 'null-body';
}
