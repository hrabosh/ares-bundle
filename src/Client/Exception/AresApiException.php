<?php

declare(strict_types=1);

namespace Lustrace\AresBundle\Client\Exception;

use Lustrace\AresBundle\Dto\AresError;
use Lustrace\AresBundle\Enum\Dataset;

final class AresApiException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?Dataset $dataset,
        public readonly ?AresError $aresError,
        string $message = 'ARES API request failed.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
