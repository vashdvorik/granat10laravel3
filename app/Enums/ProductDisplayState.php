<?php

namespace App\Enums;

enum ProductDisplayState: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Checking = 'checking';
    case ManualReview = 'manual_review';
    case ChangesRequired = 'changes_required';
    case Rejected = 'rejected';
    case ApprovedHidden = 'approved_hidden';
    case Published = 'published';
    case Unavailable = 'unavailable';
    case SellerHidden = 'seller_hidden';
}
