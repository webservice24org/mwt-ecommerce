<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Auth\ShowLoginController;
use App\Http\Controllers\Admin\Catalog\AttributeController;
use App\Http\Controllers\Admin\Catalog\AttributeValueController;
use App\Http\Controllers\Admin\Catalog\BrandController;
use App\Http\Controllers\Admin\Catalog\CategoryController;
use App\Http\Controllers\Admin\Catalog\ProductController;
use App\Http\Controllers\Admin\Catalog\ProductDescriptionMediaController;
use App\Http\Controllers\Admin\Catalog\ProductImageController;
use App\Http\Controllers\Admin\Catalog\ProductVariantController;
use App\Http\Controllers\Admin\Catalog\ProductVideoController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FooterBuilderController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\InvoiceSettingController;
use App\Http\Controllers\Admin\MailSettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\OrderTrashController;
use App\Http\Controllers\Admin\PageBuilderBrandSearchController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageBuilderImageController;
use App\Http\Controllers\Admin\PageBuilderProductSearchController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\ProductPerformanceReportController;
use App\Http\Controllers\Admin\ProductPerformanceReportExportController;
use App\Http\Controllers\Admin\Promotion\PromoCodeController;
use App\Http\Controllers\Admin\Promotion\PromotionController;
use App\Http\Controllers\Admin\Reports\ActionQueueCsvExportController;
use App\Http\Controllers\Admin\Reports\ActionQueueReportController;
use App\Http\Controllers\Admin\Reports\CodOperationsCsvExportController;
use App\Http\Controllers\Admin\Reports\CodOperationsReportController;
use App\Http\Controllers\Admin\Reports\InventoryOperationsCsvExportController;
use App\Http\Controllers\Admin\Reports\InventoryOperationsReportController;
use App\Http\Controllers\Admin\Reports\OperationalOrdersCsvExportController;
use App\Http\Controllers\Admin\Reports\OperationalOrdersReportController;
use App\Http\Controllers\Admin\Reports\ProductAnalyticsPdfExportController;
use App\Http\Controllers\Admin\Reports\ProductAnalyticsReportController;
use App\Http\Controllers\Admin\Reports\SalesAnalyticsPdfExportController;
use App\Http\Controllers\Admin\Reports\SalesAnalyticsReportController;
use App\Http\Controllers\Admin\Reviews\ProductReviewController;
use App\Http\Controllers\Admin\SalesReportController;
use App\Http\Controllers\Admin\SalesReportExportController;
use App\Http\Controllers\Admin\Seo\AiCrawlerPolicyController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkApplicationController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkApplicationWorkspaceController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkExecutionController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkPlanController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkPlanShowController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkReviewController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkReviewWorkspaceController;
use App\Http\Controllers\Admin\Seo\AiSeoBulkWorkspaceController;
use App\Http\Controllers\Admin\Seo\AiSeoEntityWorkspaceActionController;
use App\Http\Controllers\Admin\Seo\AiSeoEntityWorkspaceController;
use App\Http\Controllers\Admin\Seo\AiSeoSuggestionReviewController;
use App\Http\Controllers\Admin\Seo\AiSeoSuggestionsInboxController;
use App\Http\Controllers\Admin\Seo\GenerativeSearchReadinessWorkspaceController;
use App\Http\Controllers\Admin\Seo\GoogleAnalyticsPerformanceReportController;
use App\Http\Controllers\Admin\Seo\MerchantFeedExportController;
use App\Http\Controllers\Admin\Seo\MerchantReadinessController;
use App\Http\Controllers\Admin\Seo\MerchantStructuredDataPolicyController;
use App\Http\Controllers\Admin\Seo\SearchConsolePerformanceReportController;
use App\Http\Controllers\Admin\Seo\SeoAiGovernanceAuditController;
use App\Http\Controllers\Admin\Seo\SeoAuditInternalLinkReviewController;
use App\Http\Controllers\Admin\Seo\SeoAuditInternalLinkWorkspaceController;
use App\Http\Controllers\Admin\Seo\SeoAuditRunController;
use App\Http\Controllers\Admin\Seo\SeoAuditWorkspaceController;
use App\Http\Controllers\Admin\Seo\SeoMediaController;
use App\Http\Controllers\Admin\Seo\SeoOverviewController;
use App\Http\Controllers\Admin\Seo\SeoSettingsController;
use App\Http\Controllers\Admin\Seo\SeoTechnicalHealthController;
use App\Http\Controllers\Admin\Seo\UnifiedSeoPerformanceDashboardController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\Shipping\ShippingProviderAccountController;
use App\Http\Controllers\Admin\Shipping\ShippingRateController;
use App\Http\Controllers\Admin\Shipping\ShippingZoneController;
use App\Http\Controllers\Admin\WebsiteSettings\AdminBrandingController;
use App\Http\Controllers\Admin\WebsiteSettings\BuilderLogoUploadController;
use App\Http\Controllers\Admin\WebsiteSettings\HeaderBuilderController;
use App\Http\Controllers\Admin\WebsiteSettings\ThemeSettingsController;
use App\Models\FooterSetting;
use App\Models\HeaderSetting;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->group(
    function (): void {
        Route::get(
            '/login',
            ShowLoginController::class,
        )->name('login');

        Route::post(
            '/login',
            [LoginController::class, 'store'],
        )
            ->middleware('throttle:admin-login')
            ->name('login.store');

        Route::get(
            '/forgot-password',
            [ForgotPasswordController::class, 'create'],
        )->name('password.request');

        Route::post(
            '/forgot-password',
            [ForgotPasswordController::class, 'store'],
        )
            ->middleware('throttle:3,1')
            ->name('password.email');

        Route::get(
            '/reset-password/{token}',
            [ResetPasswordController::class, 'create'],
        )->name('password.reset');

        Route::post(
            '/reset-password',
            [ResetPasswordController::class, 'store'],
        )->name('password.store');
    },
);

Route::middleware([
    'auth:admin',
    'admin.active',
])->group(function (): void {
    Route::get(
        '/',
        DashboardController::class,
    )->name('dashboard');
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('brands', BrandController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);

    Route::resource('attributes', AttributeController::class)->except(['show']);
    Route::post('attributes/{attribute}/values', [AttributeValueController::class, 'store'])->name('attributes.values.store');
    Route::put('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'update'])->name('attributes.values.update');
    Route::delete('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'destroy'])->name('attributes.values.destroy');

    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
    Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');

    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::put('products/{product}/images/reorder', [ProductImageController::class, 'reorder'])->name('products.images.reorder');
    Route::put('products/{product}/images/{image}/featured', [ProductImageController::class, 'featured'])->whereNumber('image')->name('products.images.featured');
    Route::put('products/{product}/images/{image}', [ProductImageController::class, 'update'])->whereNumber('image')->name('products.images.update');
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->whereNumber('image')->name('products.images.destroy');
    Route::post('products/{product}/video', [ProductVideoController::class, 'store'])->name('products.video.store');
    Route::delete('products/{product}/video', [ProductVideoController::class, 'destroy'])->name('products.video.destroy');

    Route::post('products/description-media', ProductDescriptionMediaController::class)->name('products.description-media.store');
    Route::post('seo/media', SeoMediaController::class)->name('seo.media.store');

    Route::resource('pages', PageController::class)->except(['show']);

    Route::get('pages/{page}/builder', PageBuilderController::class)->name('pages.builder');
    Route::get('pages/{page}/builder/products', PageBuilderProductSearchController::class)->name('pages.builder.products');
    Route::get('pages/{page}/builder/brands', PageBuilderBrandSearchController::class)->name('pages.builder.brands');
    Route::post('pages/{page}/builder/images', PageBuilderImageController::class)->name('pages.builder.images.store');

    Route::post('pages/{page}/sections', [PageSectionController::class, 'store'])->name('pages.sections.store');
    Route::put('pages/{page}/sections/reorder', [PageSectionController::class, 'reorder'])->name('pages.sections.reorder');
    Route::post('pages/{page}/sections/{section}/duplicate', [PageSectionController::class, 'duplicate'])->whereNumber('section')->name('pages.sections.duplicate');
    Route::put('pages/{page}/sections/{section}', [PageSectionController::class, 'update'])->whereNumber('section')->name('pages.sections.update');
    Route::delete('pages/{page}/sections/{section}', [PageSectionController::class, 'destroy'])->whereNumber('section')->name('pages.sections.destroy');

    /*
    |--------------------------------------------------------------------------
    | Website Settings — Footer Builder
    |--------------------------------------------------------------------------
    |
    | B.2 establishes the protected route contract.
    | The temporary actions below are replaced by
    | FooterBuilderController in B.3.
    |
    */

    Route::get('website-settings/footer-builder', [FooterBuilderController::class, 'edit'])->middleware('can:viewAny,'.FooterSetting::class)->name('website-settings.footer-builder.edit');
    Route::put('website-settings/footer-builder', [FooterBuilderController::class, 'update'])->middleware('can:updateAny,'.FooterSetting::class)->name('website-settings.footer-builder.update');

    Route::get('website-settings/header-builder', [HeaderBuilderController::class, 'edit'])->middleware('can:viewAny,'.HeaderSetting::class)->name('website-settings.header-builder.edit');
    Route::put('website-settings/header-builder', [HeaderBuilderController::class, 'update'])->middleware('can:updateAny,'.HeaderSetting::class)->name('website-settings.header-builder.update');

    Route::post('website-settings/header-builder/logo', [BuilderLogoUploadController::class, 'header'])->middleware(['can:updateAny,'.HeaderSetting::class, 'throttle:20,1'])->name('website-settings.header-builder.logo.store');
    Route::post('website-settings/footer-builder/logo', [BuilderLogoUploadController::class, 'footer'])->middleware(['can:updateAny,'.FooterSetting::class, 'throttle:20,1'])->name('website-settings.footer-builder.logo.store');

    Route::get('website-settings/theme-settings', [ThemeSettingsController::class, 'edit'])->middleware('can:viewAny,'.ThemeSetting::class)->name('website-settings.theme-settings.edit');
    Route::put('website-settings/theme-settings/product-cards', [ThemeSettingsController::class, 'updateProductCards'])->middleware('can:updateAny,'.ThemeSetting::class)->name('website-settings.theme-settings.product-cards.update');
    Route::put('website-settings/theme-settings/admin-branding', [AdminBrandingController::class, 'update'])->middleware('can:updateAny,'.ThemeSetting::class)->name('website-settings.theme-settings.admin-branding.update');
    Route::post('website-settings/theme-settings/admin-branding/{kind}', [AdminBrandingController::class, 'upload'])->whereIn('kind', ['login_logo', 'sidebar_logo', 'background_image'])->middleware(['can:updateAny,'.ThemeSetting::class, 'throttle:20,1'])->name('website-settings.theme-settings.admin-branding.upload');

    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('invoices.show');
    Route::get('website-settings/invoice', [InvoiceSettingController::class, 'edit'])->name('website-settings.invoice.edit');
    Route::post('website-settings/invoice', [InvoiceSettingController::class, 'update'])->name('website-settings.invoice.update');

    Route::get('website-settings/mail', [MailSettingController::class, 'edit'])->name('website-settings.mail.edit');
    Route::post('website-settings/mail', [MailSettingController::class, 'update'])->name('website-settings.mail.update');

    Route::post('website-settings/mail/test', [MailSettingController::class, 'test'])->name('website-settings.mail.test');

    /*
    |--------------------------------------------------------------------------
    | Promotion processing
    |--------------------------------------------------------------------------
    */

    Route::resource('promotions', PromotionController::class)->except(['show']);
    Route::resource('promo-codes', PromoCodeController::class)->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | Oerder processing
    |--------------------------------------------------------------------------
    */

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::delete('orders/{order}', [OrderTrashController::class, 'destroy'])->whereNumber('order')->name('orders.destroy');
    Route::post('orders/{order}/restore', [OrderTrashController::class, 'restore'])->whereNumber('order')->name('orders.restore');
    Route::delete('orders/{order}/purge', [OrderTrashController::class, 'purge'])->whereNumber('order')->name('orders.purge');
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->whereNumber('order')->name('orders.confirm');
    Route::post('orders/{order}/start-processing', [OrderController::class, 'startProcessing'])->whereNumber('order')->name('orders.start-processing');
    Route::post('orders/{order}/ship', [OrderController::class, 'ship'])->whereNumber('order')->name('orders.ship');
    Route::post('orders/{order}/deliver', [OrderController::class, 'deliver'])->whereNumber('order')->name('orders.deliver');
    Route::post('orders/{order}/complete', [OrderController::class, 'complete'])->whereNumber('order')->name('orders.complete');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');

    Route::post('orders/{order}/shipments', [ShipmentController::class, 'store'])
        ->whereNumber('order')
        ->name('orders.shipments.store');

    Route::patch('orders/{order}/shipments/{shipment}/tracking', [ShipmentController::class, 'updateTracking'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->name('orders.shipments.tracking.update');

    Route::post('orders/{order}/shipments/{shipment}/start-processing', [ShipmentController::class, 'startProcessing'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->name('orders.shipments.start-processing');

    Route::post('orders/{order}/shipments/{shipment}/ship', [ShipmentController::class, 'ship'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->name('orders.shipments.ship');

    Route::post('orders/{order}/shipments/{shipment}/deliver', [ShipmentController::class, 'deliver'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->name('orders.shipments.deliver');

    Route::post('orders/{order}/shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->name('orders.shipments.cancel');

    Route::post('orders/{order}/shipments/{shipment}/return', [ShipmentController::class, 'returnShipment'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->name('orders.shipments.return');

    Route::post('orders/{order}/shipments/{shipment}/provider/submit', [ShipmentController::class, 'submitProvider'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->middleware('throttle:10,1')
        ->name('orders.shipments.provider.submit');

    Route::post('orders/{order}/shipments/{shipment}/provider/sync', [ShipmentController::class, 'syncProvider'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->middleware('throttle:10,1')
        ->name('orders.shipments.provider.sync');

    Route::post('orders/{order}/shipments/{shipment}/provider/review-resolution', [ShipmentController::class, 'resolveProviderReview'])
        ->whereNumber('order')
        ->whereNumber('shipment')
        ->middleware('throttle:5,1')
        ->name('orders.shipments.provider.review-resolution');

    Route::patch('orders/{order}/payment/due', [OrderPaymentController::class, 'due'])
        ->whereNumber('order')
        ->name(
            'orders.payment.due',
        );

    Route::patch('orders/{order}/payment/paid', [OrderPaymentController::class, 'paid'])
        ->whereNumber('order')
        ->name(
            'orders.payment.paid',
        );

    /*
    |--------------------------------------------------------------------------
    | Shipping Settings
    |--------------------------------------------------------------------------
    */

    Route::prefix('shipping/')
        ->name('shipping.')
        ->group(function (): void {
            Route::resource('zones', ShippingZoneController::class)->except(['show']);
            Route::resource('rates', ShippingRateController::class)->except(['show']);
            Route::get('providers', [ShippingProviderAccountController::class, 'index'])->name('providers.index');
            Route::get('providers/{provider}/edit', [ShippingProviderAccountController::class, 'edit'])->name('providers.edit');
            Route::put('providers/{provider}', [ShippingProviderAccountController::class, 'update'])->name('providers.update');
            Route::delete('providers/{provider}', [ShippingProviderAccountController::class, 'disconnect'])->name('providers.disconnect');
            Route::post('providers/{provider}/test', [ShippingProviderAccountController::class, 'testConnection'])->name('providers.test');
            Route::put('providers/{provider}/operational-settings', [ShippingProviderAccountController::class, 'updateOperationalSettings'])->name('providers.operational-settings');
        });

    /*
    |--------------------------------------------------------------------------
    | Customer Management
    |--------------------------------------------------------------------------
    */

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('customers.edit');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('customers.update');
    Route::patch('customers/{customer}/block', [CustomerController::class, 'block'])->whereNumber('customer')->name('customers.block');
    Route::patch('customers/{customer}/unblock', [CustomerController::class, 'unblock'])->whereNumber('customer')->name('customers.unblock');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->whereNumber('customer')->name('customers.destroy');

    /*
    |--------------------------------------------------------------------------
    | Review Management
    |--------------------------------------------------------------------------
    */
    Route::get('reviews', [ProductReviewController::class, 'index'])->name('reviews.index');
    Route::post('reviews/{review}/approve', [ProductReviewController::class, 'approve'])->whereNumber('review')->name('reviews.approve');
    Route::post('reviews/{review}/reject', [ProductReviewController::class, 'reject'])->whereNumber('review')->name('reviews.reject');

    Route::get('reports/sales', SalesReportController::class)->name('reports.sales.index');
    Route::get('reports/products', ProductPerformanceReportController::class)->name('reports.products.index');
    Route::get('reports/sales/export', SalesReportExportController::class)->name('reports.sales.export');
    Route::get('reports/products/export', ProductPerformanceReportExportController::class)->name('reports.products.export');
    Route::get('reports/sales-analytics', SalesAnalyticsReportController::class)->name('reports.sales.analytics');
    Route::get('reports/sales-analytics/pdf', SalesAnalyticsPdfExportController::class)->name('reports.sales.analytics.pdf');
    Route::get('reports/product-analytics', ProductAnalyticsReportController::class)->name('reports.products.analytics');
    Route::get('reports/product-analytics/pdf', ProductAnalyticsPdfExportController::class)->name('reports.products.analytics.pdf');
    Route::get('reports/operations/orders', OperationalOrdersReportController::class)->name('reports.operations.orders');
    Route::get('reports/operations/orders/export.csv', OperationalOrdersCsvExportController::class)->name('reports.operations.orders.csv');
    Route::get('reports/operations/cod', CodOperationsReportController::class)->name('reports.operations.cod');
    Route::get('reports/operations/cod/export.csv', CodOperationsCsvExportController::class)->name('reports.operations.cod.csv');
    Route::get('reports/operations/inventory', InventoryOperationsReportController::class)->name('reports.operations.inventory');
    Route::get('reports/operations/inventory/export.csv', InventoryOperationsCsvExportController::class)->name('reports.operations.inventory.csv');
    Route::get('reports/operations/action-queue', ActionQueueReportController::class)->name('reports.operations.action-queue');
    Route::get('reports/operations/action-queue/export.csv', ActionQueueCsvExportController::class)->name('reports.operations.action-queue.csv');

    /*
    |--------------------------------------------------------------------------
    | SEO Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('seo/')->name('seo.')->group(function (): void {
        Route::get('settings', [SeoSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SeoSettingsController::class, 'update'])->name('settings.update');
        Route::get('overview', SeoOverviewController::class)->name('overview');
        Route::get('suggestions', AiSeoSuggestionsInboxController::class)->name('suggestions.index');
        Route::post('suggestions/{suggestionId}/approve', [AiSeoSuggestionReviewController::class, 'approve'])->name('suggestions.approve');
        Route::post('suggestions/{suggestionId}/reject', [AiSeoSuggestionReviewController::class, 'reject'])->name('suggestions.reject');

        Route::get('entities/{entityType}/{entityId}', AiSeoEntityWorkspaceController::class)->name('entities.show');
        Route::post('entities/{entityType}/{entityId}/analyze', [AiSeoEntityWorkspaceActionController::class, 'analyze'])->name('entities.analyze');
        Route::post('entities/{entityType}/{entityId}/suggestions/generate', [AiSeoEntityWorkspaceActionController::class, 'generateSuggestions'])->name('entities.suggestions.generate');
        Route::post('entities/{entityType}/{entityId}/analyses/{analysisId}/enrich', [AiSeoEntityWorkspaceActionController::class, 'enrichAnalysis'])->name('entities.analyses.enrich');

        Route::get('technical-health', SeoTechnicalHealthController::class)->name('technical-health');
        Route::get('bulk-ai', AiSeoBulkWorkspaceController::class)->name('bulk.index');
        Route::post('bulk-ai/plans', [AiSeoBulkPlanController::class, 'store'])->name('bulk.plans.store');

        Route::get('bulk-ai/plans/{bulkJobId}', AiSeoBulkPlanShowController::class)->name('bulk.plans.show');
        Route::post('bulk-ai/plans/{bulkJobId}/execute', [AiSeoBulkExecutionController::class, 'execute'])->name('bulk.plans.execute');
        Route::post('bulk-ai/plans/{bulkJobId}/retry-failed', [AiSeoBulkExecutionController::class, 'retryFailed'])->name('bulk.plans.retry-failed');

        Route::get('bulk-ai/plans/{bulkJobId}/review', AiSeoBulkReviewWorkspaceController::class)->name('bulk.plans.review');
        Route::post('bulk-ai/plans/{bulkJobId}/review', [AiSeoBulkReviewController::class, 'store'])->name('bulk.plans.review.store');
        Route::get('bulk-ai/plans/{bulkJobId}/apply', AiSeoBulkApplicationWorkspaceController::class)->name('bulk.plans.apply');
        Route::post('bulk-ai/plans/{bulkJobId}/apply', [AiSeoBulkApplicationController::class, 'store'])->name('bulk.plans.apply.store');

        Route::get('generative-search/crawlers', [AiCrawlerPolicyController::class, 'index'])->name('generative-search.crawlers.index');
        Route::put('generative-search/crawlers', [AiCrawlerPolicyController::class, 'update'])->name('generative-search.crawlers.update');

        Route::get('generative-search/readiness', GenerativeSearchReadinessWorkspaceController::class)->name('generative-search.readiness');
        Route::get('merchant/policies', [MerchantStructuredDataPolicyController::class, 'index'])->name('merchant.policies.index');
        Route::put('merchant/policies', [MerchantStructuredDataPolicyController::class, 'update'])->name('merchant.policies.update');

        Route::get('merchant/readiness', MerchantReadinessController::class)->name('merchant.readiness.index');
        Route::post('merchant/exports', [MerchantFeedExportController::class, 'store'])->name('merchant.exports.store');
        Route::post('merchant/exports/{export:public_id}/retry', [MerchantFeedExportController::class, 'retry'])->name('merchant.exports.retry');
        Route::get('merchant/exports/{export:public_id}/download', [MerchantFeedExportController::class, 'download'])->middleware('throttle:30,1')->name('merchant.exports.download');

        Route::get('audits', SeoAuditWorkspaceController::class)->name('audits.index');
        Route::post('audits/runs', [SeoAuditRunController::class, 'store'])->middleware('throttle:3,1')->name('audits.runs.store');
        Route::get('internal-links', SeoAuditInternalLinkWorkspaceController::class)->name('internal-links.index');
        Route::patch('internal-links/{run:public_id}/{fingerprint}', [SeoAuditInternalLinkReviewController::class, 'update'])->where('fingerprint', '[a-f0-9]{64}')->middleware('throttle:30,1')->name('internal-links.review');
        Route::get('performance/search-console', SearchConsolePerformanceReportController::class)->name('performance.search-console.index');
        Route::get('performance/google-analytics', GoogleAnalyticsPerformanceReportController::class)->name('performance.google-analytics.index');
        Route::get('performance', UnifiedSeoPerformanceDashboardController::class)->name('performance.index');
        Route::get('ai-governance', SeoAiGovernanceAuditController::class)->name('ai-governance.index');

    });

});

Route::middleware([
    'auth:admin',
    'admin.active',
    'admin.role:super_admin',
])->group(function (): void {
    Route::resource('admins', AdminController::class)->except(['show']);

});
