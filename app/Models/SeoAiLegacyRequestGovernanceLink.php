<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $ai_seo_request_id
 * @property int $governance_run_id
 */
final class SeoAiLegacyRequestGovernanceLink extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'ai_seo_request_id',
        'governance_run_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ai_seo_request_id' => 'integer',
            'governance_run_id' => 'integer',
        ];
    }
}
