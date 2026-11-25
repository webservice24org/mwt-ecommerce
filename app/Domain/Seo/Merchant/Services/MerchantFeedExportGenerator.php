<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantFeedExportFileData;
use App\Domain\Seo\Merchant\Data\MerchantFeedExportSnapshotData;
use App\Domain\Seo\Merchant\Exceptions\MerchantFeedExportSnapshotChanged;
use App\Domain\Seo\Merchant\Queries\GetMerchantReadinessWorkspaceQuery;
use App\Models\MerchantFeedExport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class MerchantFeedExportGenerator
{
    private const MIME_TYPE =
        'text/tab-separated-values';

    public function __construct(
        private MerchantProductFeedCatalogBuilder $catalog,
        private GetMerchantReadinessWorkspaceQuery $workspace,
        private MerchantFeedExportSnapshotFactory $snapshots,
        private MerchantTsvFeedSerializer $tsv,
    ) {}

    public function generate(
        MerchantFeedExport $export,
    ): MerchantFeedExportFileData {
        if (
            $export->schema_version !==
            MerchantFeedExportSnapshotData::SCHEMA_VERSION
        ) {
            throw new MerchantFeedExportSnapshotChanged(
                'Merchant feed export schema version no longer matches the current generator.',
            );
        }

        $catalog =
            $this->catalog
                ->build();

        $workspace =
            $this->workspace
                ->fromCatalog(
                    $catalog,
                );

        $currentSnapshot =
            $this->snapshots
                ->make(
                    workspace: $workspace,

                    format: $export->format,

                    catalog: $catalog,
                );

        if (
            ! hash_equals(
                $export->snapshot_hash,
                $currentSnapshot->hash,
            )
        ) {
            throw new MerchantFeedExportSnapshotChanged(
                'Merchant feed data changed after this export was requested. Request a new export to capture the current catalog.',
            );
        }

        $contents =
            $this->tsv
                ->serialize(
                    $catalog->items,
                );

        $contentSha256 =
            hash(
                'sha256',
                $contents,
            );

        $expectedContentSha256 =
            $export->snapshot[
                'feed_content_sha256'
            ] ?? null;

        if (
            ! is_string(
                $expectedContentSha256,
            )
            || preg_match(
                '/^[a-f0-9]{64}$/',
                $expectedContentSha256,
            ) !== 1
            || ! hash_equals(
                $expectedContentSha256,
                $contentSha256,
            )
        ) {
            throw new MerchantFeedExportSnapshotChanged(
                'Merchant feed content fingerprint is missing or no longer matches this export snapshot.',
            );
        }

        $diskName =
            $this->diskName();

        $directory =
            $this->directory();

        $path =
            $directory
            .'/'.$export->public_id
            .'/merchant-products.tsv';

        $name =
            'merchant-products-'
            .$export->public_id
            .'.tsv';

        $disk =
            Storage::disk(
                $diskName,
            );

        if (
            $this->fileExists(
                $disk,
                $path,
            )
        ) {
            return $this->existingFile(
                disk: $disk,

                diskName: $diskName,

                path: $path,

                name: $name,

                expectedSha256: $contentSha256,
            );
        }

        $temporaryPath =
            $directory
            .'/.tmp/'
            .$export->public_id
            .'-'.Str::uuid()
            .'.tsv';

        try {
            $written =
                $disk->put(
                    $temporaryPath,
                    $contents,
                );

            if (! $written) {
                throw new RuntimeException(
                    'Merchant feed export temporary file could not be written.',
                );
            }

            $moved =
                $disk->move(
                    $temporaryPath,
                    $path,
                );

            if (
                ! $moved
                && ! $this->fileExists(
                    $disk,
                    $path,
                )
            ) {
                throw new RuntimeException(
                    'Merchant feed export file could not be finalized.',
                );
            }
        } finally {
            if (
                $this->fileExists(
                    $disk,
                    $temporaryPath,
                )
            ) {
                $disk->delete(
                    $temporaryPath,
                );
            }
        }

        return $this->existingFile(
            disk: $disk,

            diskName: $diskName,

            path: $path,

            name: $name,

            expectedSha256: $contentSha256,
        );
    }

    /**
     * Filesystem state can change after write/move operations.
     *
     * @phpstan-impure
     */
    private function fileExists(
        Filesystem $disk,
        string $path,
    ): bool {
        return $disk->exists(
            $path,
        );
    }

    private function diskName(): string
    {
        $disk =
            trim(
                (string) config(
                    'merchant-feed.exports.disk',
                    'local',
                ),
            );

        if ($disk === '') {
            throw new RuntimeException(
                'Merchant feed export disk is not configured.',
            );
        }

        return $disk;
    }

    private function directory(): string
    {
        $directory =
            trim(
                (string) config(
                    'merchant-feed.exports.directory',
                    'merchant-feed-exports',
                ),
                " \/\\",
            );

        if (
            $directory === ''
            || str_contains(
                $directory,
                '..',
            )
        ) {
            throw new RuntimeException(
                'Merchant feed export directory is invalid.',
            );
        }

        return $directory;
    }

    private function existingFile(
        Filesystem $disk,
        string $diskName,
        string $path,
        string $name,
        string $expectedSha256,
    ): MerchantFeedExportFileData {
        $contents =
            $disk->get(
                $path,
            );

        if (! is_string($contents)) {
            throw new RuntimeException(
                'Merchant feed export file could not be read for verification.',
            );
        }

        $actualSha256 =
            hash(
                'sha256',
                $contents,
            );

        if (
            ! hash_equals(
                $expectedSha256,
                $actualSha256,
            )
        ) {
            throw new RuntimeException(
                'Existing merchant feed export file does not match the expected content hash.',
            );
        }

        return new MerchantFeedExportFileData(
            disk: $diskName,

            path: $path,

            name: $name,

            mimeType: self::MIME_TYPE,

            size: strlen(
                $contents,
            ),

            sha256: $actualSha256,
        );
    }
}
