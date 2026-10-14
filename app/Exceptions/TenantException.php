<?php

namespace App\Exceptions;

use RuntimeException;

/** خطای مرز Tenant: نوشتن بدون ساختمان فعال یا نوشتن در ساختمان دیگر. */
class TenantException extends RuntimeException
{
}
