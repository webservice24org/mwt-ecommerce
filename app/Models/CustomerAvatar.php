<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property string $mime_type
 * @property string $image_base64
 * @property string $version
 */
final class CustomerAvatar extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'mime_type',
        'image_base64',
        'version',
    ];

    /** @var list<string> */
    protected $hidden = ['image_base64'];
}
