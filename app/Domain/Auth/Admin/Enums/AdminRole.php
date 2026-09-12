<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Enums;

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Editor = 'editor';
}
