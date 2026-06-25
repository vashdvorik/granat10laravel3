<?php

namespace App\Enums;

enum ProductModerationStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Processing = 'processing';
    case Approved = 'approved';
    case NeedsReview = 'needs_review';
    case ChangesRequired = 'changes_required';
    case Rejected = 'rejected';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Pending => 'На проверке',
            self::Processing => 'Проверяется',
            self::Approved => 'Одобрен',
            self::NeedsReview => 'На проверке у администратора',
            self::ChangesRequired => 'Нужно редактировать',
            self::Rejected => 'Отклонён',
            self::Failed => 'Ошибка проверки',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending, self::Processing => 'warning',
            self::Approved => 'success',
            self::NeedsReview, self::ChangesRequired => 'info',
            self::Rejected, self::Failed => 'danger',
        };
    }
}
