<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use RuntimeException;

final class InvalidCategoryHierarchyException extends RuntimeException {}
