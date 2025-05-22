<?php
namespace Modules\Core\app\Support\DTO;

class ServiceResponseDTO
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $message = null,
        public ?int $code = 200,
    ) {}

    public static function ok(mixed $data, string $message = 'OK'): self
    {
        return new self(true, $data, $message, 200);
    }

    public static function fail(string $message, int $code = 500): self
    {
        return new self(false, null, $message, $code);
    }
}

