<?php
declare(strict_types=1);

namespace Ndrstmr\Icap\Exception;

/**
 * Thrown when the read operation exceeds the configured timeout.
 */
class IcapTimeoutException extends IcapClientException
{
}
