<?php

namespace Modules\Core\app\Support\Logging;


use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;

class CustomizeFormatter
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        foreach ($monolog->getHandlers() as $handler) {

            $format = "[%datetime%] %channel%.%level_name%: %message% │ %context.file%:%context.line%\n";

            $handler->setFormatter(new LineFormatter(
                $format,
                'Y-m-d H:i:s',
                true,
                true
            ));
        }
    }
}

