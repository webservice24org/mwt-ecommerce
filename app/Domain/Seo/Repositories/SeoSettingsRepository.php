<?php

declare(strict_types=1);

namespace App\Domain\Seo\Repositories;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsDefaults;
use App\Domain\Seo\Services\SeoSettingsMapper;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\DB;

final readonly class SeoSettingsRepository
{
    public function __construct(
        private SeoSettingsDefaults $defaults,

        private SeoSettingsMapper $mapper,
    ) {}

    public function get(): SeoSettingsData
    {
        $setting =
            SeoSetting::query()
                ->first();

        if (
            $setting === null
        ) {
            return $this
                ->defaults
                ->make();
        }

        return $this
            ->mapper
            ->fromModel(
                $setting,
            );
    }

    public function save(
        SeoSettingsData $data,
    ): SeoSettingsData {
        return DB::transaction(
            function () use ($data): SeoSettingsData {
                $setting =
                    SeoSetting::query()
                        ->lockForUpdate()
                        ->first();

                if (
                    $setting === null
                ) {
                    $setting =
                        new SeoSetting;
                }

                $setting->fill(
                    $data->toArray(),
                );

                $setting->save();

                return $this
                    ->mapper
                    ->fromModel(
                        $setting,
                    );
            },
        );
    }
}
