<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Enum;

enum DatasetStatus: string {
    case OK = 'ok';
    case NOT_FOUND = 'not_found';
    case ERROR = 'error';
}
