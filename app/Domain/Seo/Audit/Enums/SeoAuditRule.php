<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Enums;

enum SeoAuditRule: string
{
    case OrphanProduct = 'orphan_product';
    case OrphanCategory = 'orphan_category';
    case BrokenInternalLink = 'broken_internal_link';
    case RedirectChain = 'redirect_chain';
    case DuplicateCanonical = 'duplicate_canonical';
    case CanonicalToNon200 = 'canonical_to_non_200';
    case IndexableMissingCanonical = 'indexable_missing_canonical';
    case IndexableMissingTitle = 'indexable_missing_title';
    case EmptyDescription = 'empty_description';
    case DuplicateTitle = 'duplicate_title';
    case DuplicateDescription = 'duplicate_description';
    case MissingH1 = 'missing_h1';
    case MultipleH1 = 'multiple_h1';
    case BrokenImage = 'broken_image';
    case MissingAlt = 'missing_alt';
    case ThinCategoryPage = 'thin_category_page';
}
