<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class ReceptionReleaseGate extends Command
{
    protected $signature = 'reception:release-gate {--loopback-demo : Permite HTTP exclusivamente en loopback para una demo ficticia}';
    protected $description = 'Bloquea una entrega con debug, HTTP público, dependencia incompleta o secretos ausentes';

    public function handle(): int
    {
        $url = parse_url((string) config('app.url'));
        $loopback = in_array($url['host'] ?? '', ['127.0.0.1','localhost','[::1]'],true);
        $checks = ['environment'=>app()->environment(['staging','production']),
            'https'=>($url['scheme'] ?? '') === 'https' || ($this->option('loopback-demo') && $loopback),
            'security_provider'=>app()->getProvider(\App\Providers\ReceptionSecurityServiceProvider::class) !== null,
            'refresh_cookie_secure'=>(bool) config('tenants.refresh.cookie_secure'),
            'persistent_rate_limiter'=>!in_array(config('cache.default'),['array','null'],true)];
        $readiness = $this->callSilent('reception:readiness',['--json'=>true]);
        $checks['readiness'] = $readiness === 0;
        foreach ($checks as $name=>$passed) $this->line(($passed ? 'PASS ' : 'FAIL ').$name);
        return in_array(false,$checks,true) ? self::FAILURE : self::SUCCESS;
    }
}
