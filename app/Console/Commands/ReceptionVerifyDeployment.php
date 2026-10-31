<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class ReceptionVerifyDeployment extends Command
{
    protected $signature = 'reception:verify-deployment {fixture} {--url=http://127.0.0.1:8096}';
    protected $description = 'Ejecuta caja negra HTTP sobre órdenes ficticias de una instalación staging';

    public function handle(): int
    {
        if (!app()->environment('staging')) { $this->error('Solo APP_ENV=staging'); return self::FAILURE; }
        $url = rtrim((string) $this->option('url'), '/'); $parts = parse_url($url);
        if (!$parts || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !in_array($parts['scheme'] ?? '', ['http','https'], true)
            || (($parts['scheme'] ?? '') === 'http' && !in_array($parts['host'] ?? '', ['127.0.0.1','localhost','[::1]'], true))) {
            $this->error('Se exige HTTPS o HTTP de loopback'); return self::FAILURE;
        }
        $fixture = json_decode(file_get_contents($this->argument('fixture')), true, 512, JSON_THROW_ON_ERROR);
        $client = Http::baseUrl($url.'/api/v1')->acceptJson()->timeout(15)->withHeaders(['X-Tenant-ID'=>$fixture['hospital']]);
        $results = [];
        try {
            $login = $client->post('/auth/login', ['email'=>$fixture['users']['tecnico_laboratorio'],'password'=>$fixture['password']])->throw();
            $token = $login->json('access_token');
            if (!is_string($token) || $token === '') throw new \RuntimeException('Login sin token');
            $client = $client->withToken($token);
            $results['jwt_login'] = true;
            $payload = ['request_key'=>(string) Str::uuid(),'item_id'=>$fixture['accepted_item'],
                'patient_code'=>$fixture['patient_code'],'patient_name'=>$fixture['patient_name'],
                'type'=>'blood','container'=>'EDTA','volume'=>3,'temperature'=>20,'collected_at'=>now()->subMinute()->format(DATE_ATOM)];
            $first = $client->post('/lab/specimens', $payload)->throw();
            $results['accepted'] = $first->status() === 201 && $first->json('data.status') === 'accepted';
            $replay = $client->post('/lab/specimens', $payload)->throw();
            $results['idempotent_replay'] = $first->json() === $replay->json();
            $code = $first->json('data.code');
            $results['lookup'] = $client->get('/lab/specimens/'.$code)->throw()->json('data.code') === $code;
            $results['label'] = $client->get('/lab/specimens/'.$code.'/label')->throw()->json('data.code') === $code;
            $payload['request_key'] = (string) Str::uuid(); $payload['item_id'] = $fixture['rejected_item']; $payload['derramada'] = true;
            $rejected = $client->post('/lab/specimens', $payload)->throw();
            $results['clinical_rejection'] = $rejected->json('data.reason') === 'derramada' && $rejected->json('data.code') === null;
            $unauth = Http::baseUrl($url.'/api/v1')->acceptJson()->withHeaders(['X-Tenant-ID'=>$fixture['hospital']])->get('/lab/specimens/'.$code);
            $results['jwt_required'] = $unauth->status() === 401;
            $reader = $client->post('/auth/login', ['email'=>$fixture['users']['bioquimico'],'password'=>$fixture['password']])->throw()->json('access_token');
            $results['reader_cannot_receive'] = $client->withToken($reader)->post('/lab/specimens', $payload)->status() === 403;
        } catch (\Throwable) { $results['http_flow'] = false; }
        foreach ($results as $name => $passed) $this->line(($passed ? 'PASS ' : 'FAIL ').$name);
        return in_array(false, $results, true) ? self::FAILURE : self::SUCCESS;
    }
}
