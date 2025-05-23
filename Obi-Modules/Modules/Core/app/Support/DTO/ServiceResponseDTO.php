<?php
namespace Modules\Core\app\Support\DTO;

class ServiceResponseDTO
{
    public bool  $success;
    public mixed $data;
    public string $message;
    public int    $code;

    private function __construct(bool $success, mixed $data, string $message, int $code)
    {
        $this->success = $success;
        $this->data    = $data;
        $this->message = $message;
        $this->code    = $code;
    }

    public static function ok(mixed $data, string $message = 'OK', int $code = 200): self
    {
        return new self(true, $data, $message, $code);
    }

    public static function fail(string $message, int $code = 400): self
    {
        return new self(false, null, $message, $code);
    }
}
