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

    public const HEADER_HOST = 'Host';
    public const HEADER_USER_AGENT = 'User-Agent';
    public const HEADER_CONNECTION = 'Connection';
    public const HEADER_ENCAPSULATED = 'Encapsulated';
}
