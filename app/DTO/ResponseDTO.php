<?php

namespace App\DTO;
use App\DTO\Base\DTOBase;

class ResponseDTO extends DTOBase
{
    public function __construct(
        public string $status,
        public string $message,
        public mixed  $content = null
    ) { }
}
