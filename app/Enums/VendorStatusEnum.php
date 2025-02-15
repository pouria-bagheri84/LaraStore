<?php

namespace App\Enums;

enum VendorStatusEnum: string
{
    case PENDING = 'pending';

    case APPROVED = 'approved';

    case REJECTED = 'rejected';
}
