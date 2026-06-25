<?php

namespace App\Enums;

enum ProductSaveAction: string
{
    case Draft = 'save_draft';
    case Submit = 'save_and_submit';
    case Save = 'save';

    public function requestsModeration(): bool
    {
        return $this === self::Submit;
    }
}
