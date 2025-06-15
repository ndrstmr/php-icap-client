<?php
declare(strict_types=1);

namespace IcapClient;

/**
 * Constants for the ICAP protocol used by IcapClient.
 */
final class IcapProtocolConstants
{
    /** ICAP protocol version */
    public const VERSION = 'ICAP/1.0';

    /** Prefix used to identify ICAP protocol lines */
    public const PREFIX = 'ICAP/';

    // ICAP methods
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_RESPMOD = 'RESPMOD';
    public const METHOD_REQMOD = 'REQMOD';

    // Common header names
    public const HEADER_HOST = 'Host';
    public const HEADER_USER_AGENT = 'User-Agent';
    public const HEADER_CONNECTION = 'Connection';
    public const HEADER_ENCAPSULATED = 'Encapsulated';

    // Encapsulated section identifiers
    public const SECTION_REQ_HDR = 'req-hdr';
    public const SECTION_RES_HDR = 'res-hdr';
    public const SECTION_REQ_BODY = 'req-body';
    public const SECTION_RES_BODY = 'res-body';
    public const SECTION_NULL_BODY = 'null-body';
}
