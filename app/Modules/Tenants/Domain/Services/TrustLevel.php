<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
enum TrustLevel: string
{
    case UNKNOWN = 'unknown';
    case SUSPICIOUS = 'suspicious';
    case LOW_TRUST = 'low_trust';
    case TRUSTED = 'trusted';}
