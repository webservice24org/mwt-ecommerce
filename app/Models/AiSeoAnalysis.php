<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property AiSeoEntityType $entity_type
 * @property int $entity_id
 * @property string $analysis_version
 * @property string $snapshot_schema_version
 * @property string $snapshot_hash
 * @property string $analysis_input_hash
 * @property array<string, mixed> $source_snapshot
 * @property array<string, mixed> $resolved_seo
 * @property int $score
 * @property AiSeoAnalysisGrade $grade
 * @property array<string, mixed> $breakdown
 * @property list<array<string, mixed>> $rules
 * @property list<array<string, mixed>> $findings
 * @property int|null $analyzed_by_admin_id
 * @property Carbon $analyzed_at
 * @property Carbon $created_at
 */
final class AiSeoAnalysis extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'analysis_version',
        'snapshot_schema_version',
        'snapshot_hash',
        'analysis_input_hash',
        'source_snapshot',
        'resolved_seo',
        'score',
        'grade',
        'breakdown',
        'rules',
        'findings',
        'analyzed_by_admin_id',
        'analyzed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_type' => AiSeoEntityType::class,

            'entity_id' => 'integer',

            'source_snapshot' => 'array',

            'resolved_seo' => 'array',

            'score' => 'integer',

            'grade' => AiSeoAnalysisGrade::class,

            'breakdown' => 'array',

            'rules' => 'array',

            'findings' => 'array',

            'analyzed_by_admin_id' => 'integer',

            'analyzed_at' => 'datetime',

            'created_at' => 'datetime',
        ];
    }
}
