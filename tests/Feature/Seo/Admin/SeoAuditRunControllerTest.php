<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Jobs\Seo\Audit\ProcessSeoAuditRun;
use App\Models\Admin;
use App\Models\SeoAuditRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SeoAuditRunControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://example.com',
        ]);
    }

    public function test_authorized_editor_can_start_one_queued_audit(): void
    {
        Queue::fake();

        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->post(route('admin.seo.audits.runs.store'));

        $run = SeoAuditRun::query()->sole();

        $response
            ->assertRedirect(route('admin.seo.audits.index', [
                'run' => $run->public_id,
            ]))
            ->assertSessionHas('success');

        self::assertSame(
            SeoAuditRunStatus::Requested,
            $run->status,
        );

        Queue::assertPushed(
            ProcessSeoAuditRun::class,
            1,
        );
    }

    public function test_active_audit_blocks_a_second_site_wide_run(): void
    {
        Queue::fake();

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        SeoAuditRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'requested_by_admin_id' => $admin->id,
            'requested_by_name' => $admin->name,
            'requested_by_email' => $admin->email,
            'status' => SeoAuditRunStatus::Running,
            'rules_version' => 'seo-audit-rules-v1',
            'requested_at' => now(),
            'started_at' => now(),
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->from(route('admin.seo.audits.index'))
            ->post(route('admin.seo.audits.runs.store'))
            ->assertRedirect(route('admin.seo.audits.index'))
            ->assertSessionHasErrors('audit');

        self::assertSame(
            1,
            SeoAuditRun::query()->count(),
        );

        Queue::assertNothingPushed();
    }

    public function test_guest_cannot_start_audit(): void
    {
        Queue::fake();

        $this
            ->post(route('admin.seo.audits.runs.store'))
            ->assertRedirect(route('admin.login'));

        Queue::assertNothingPushed();
    }
}
