<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use App\Models\Admin;
use App\Models\MerchantFeedExport;
use DomainException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class MerchantFeedExportDownload
{
    private const MIME_TYPE =
        'text/tab-separated-values';

    public function __construct(
        private MerchantFeedExportLifecycle $lifecycle,
    ) {}

    public function response(
        MerchantFeedExport $export,
        Admin $admin,
    ): StreamedResponse {
        $this->assertDownloadable(
            $export,
        );

        $diskName =
            (string) $export->file_disk;

        $path =
            $this->safePath(
                $export,
            );

        $name =
            $this->safeName(
                $export,
            );

        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                $diskName,
            );

        if (! $disk->exists($path)) {
            throw new RuntimeException(
                'Merchant feed export file is missing from storage.',
            );
        }

        $this->assertIntegrity(
            disk: $disk,

            path: $path,

            export: $export,
        );

        $this->lifecycle
            ->recordDownloadRequested(
                export: $export,

                admin: $admin,
            );

        return $disk->download(
            path: $path,

            name: $name,

            headers: [
                'Content-Type' => self::MIME_TYPE,

                'Cache-Control' => 'private, no-store, max-age=0',

                'Pragma' => 'no-cache',

                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function assertDownloadable(
        MerchantFeedExport $export,
    ): void {
        if (
            $export->status !==
            MerchantFeedExportStatus::Completed
        ) {
            throw new DomainException(
                'Merchant feed export is not completed.',
            );
        }

        if (
            $export->file_disk === null
            || trim($export->file_disk) === ''
            || $export->file_path === null
            || trim($export->file_path) === ''
            || $export->file_name === null
            || trim($export->file_name) === ''
            || $export->file_mime_type !==
                self::MIME_TYPE
            || $export->file_size === null
            || $export->file_size < 0
            || $export->file_sha256 === null
            || preg_match(
                '/^[a-f0-9]{64}$/i',
                $export->file_sha256,
            ) !== 1
        ) {
            throw new RuntimeException(
                'Merchant feed export file metadata is incomplete.',
            );
        }
    }

    private function safePath(
        MerchantFeedExport $export,
    ): string {
        $path =
            str_replace(
                '\\',
                '/',
                trim(
                    (string) $export->file_path,
                ),
            );

        $expectedSuffix =
            '/'.$export->public_id
            .'/merchant-products.tsv';

        if (
            $path === ''
            || str_starts_with(
                $path,
                '/',
            )
            || str_contains(
                $path,
                '..',
            )
            || ! str_ends_with(
                '/'.$path,
                $expectedSuffix,
            )
        ) {
            throw new RuntimeException(
                'Merchant feed export storage path is invalid.',
            );
        }

        return $path;
    }

    private function safeName(
        MerchantFeedExport $export,
    ): string {
        $expected =
            'merchant-products-'
            .$export->public_id
            .'.tsv';

        if (
            $export->file_name !==
            $expected
        ) {
            throw new RuntimeException(
                'Merchant feed export download name is invalid.',
            );
        }

        return $expected;
    }

    private function assertIntegrity(
        FilesystemAdapter $disk,
        string $path,
        MerchantFeedExport $export,
    ): void {
        $stream =
            $disk->readStream(
                $path,
            );

        if (! is_resource($stream)) {
            throw new RuntimeException(
                'Merchant feed export file could not be opened for verification.',
            );
        }

        $hash =
            hash_init(
                'sha256',
            );

        $bytes = 0;

        try {
            while (! feof($stream)) {
                $chunk =
                    fread(
                        $stream,
                        8192,
                    );

                if ($chunk === false) {
                    throw new RuntimeException(
                        'Merchant feed export file could not be read for verification.',
                    );
                }

                if ($chunk === '') {
                    continue;
                }

                $bytes +=
                    strlen(
                        $chunk,
                    );

                hash_update(
                    $hash,
                    $chunk,
                );
            }
        } finally {
            fclose(
                $stream,
            );
        }

        $actualSha256 =
            hash_final(
                $hash,
            );

        if (
            $bytes !==
                $export->file_size
            || ! hash_equals(
                (string) $export->file_sha256,
                $actualSha256,
            )
        ) {
            throw new RuntimeException(
                'Merchant feed export file failed integrity verification.',
            );
        }
    }
}
