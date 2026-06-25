<?php

namespace App\Enums;

enum ProductPrimaryAction: string
{
    case None = 'none';
    case Submit = 'submit';
    case Edit = 'edit';
    case Publish = 'publish';
    case PushUp = 'push_up';
    case ResolveUnavailable = 'resolve_unavailable';
}
