<?php

namespace App\Http\Middleware;

use App\Jobs\RecordWebAnalytics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CollectWebIntelligence
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next($request);

        if (! config('web-intelligence.enabled') || collect(config('web-intelligence.excluded_paths', []))->contains(fn (string $pattern) => $request->is($pattern))) return $response;

        try {
            $ip = (string) ($request->ip() ?: 'unknown');
            $key = (string) config('app.key', 'moldpack');
            $ipHash = hash_hmac('sha256', $ip, $key);
            $path = Str::limit('/'.ltrim($request->path(), '/'), 500, '');
            $ua = Str::lower(Str::limit((string) $request->userAgent(), 500, ''));
            $security = $this->securitySignal($path, $ua, $response->getStatusCode());
            $referrerHost = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST) ?: null;
            $event = [
                'occurred_at' => now(),
                'session_hash' => $request->hasSession() ? hash_hmac('sha256', $request->session()->getId(), $key) : null,
                'ip_hash' => $ipHash, 'ip_masked' => $this->maskIp($ip),
                'method' => Str::limit($request->method(), 10, ''), 'path' => $path,
                'route_name' => Str::limit((string) optional($request->route())->getName(), 180, '') ?: null,
                'status_code' => $response->getStatusCode(),
                'duration_ms' => max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)),
                'referrer_host' => $referrerHost ? Str::limit(Str::lower($referrerHost), 255, '') : null,
                'source' => $this->source($referrerHost, $request->host()),
                'device' => $this->device($ua), 'browser' => $this->browser($ua), 'platform' => $this->platform($ua),
                'visitor_class' => $security ? 'suspicious' : ($this->isLikelyBot($ua) ? 'likely_bot' : 'human'),
                'country_code' => config('web-intelligence.trust_cloudflare_country') ? $this->countryCode($request) : null,
                'utm_source' => $this->safeCampaign($request->query('utm_source')),
                'utm_medium' => $this->safeCampaign($request->query('utm_medium')),
                'utm_campaign' => $this->safeCampaign($request->query('utm_campaign'), 180),
            ];
            $securityEvent = $security ? [
                'occurred_at' => now(), 'ip_hash' => $ipHash, 'ip_masked' => $event['ip_masked'],
                'event_type' => $security['type'], 'path' => $path, 'method' => $event['method'],
                'status_code' => $event['status_code'], 'action' => $event['status_code'] === 429 ? 'rate_limited' : 'observed',
                'risk' => $security['risk'], 'evidence' => ['signal' => $security['signal']],
            ] : null;
            RecordWebAnalytics::dispatch($event, $securityEvent)->afterResponse();
        } catch (\Throwable $exception) {
            report($exception); // Fail-open: telemetry never interrupts the public request.
        }

        return $response;
    }

    private function securitySignal(string $path, string $ua, int $status): ?array
    {
        foreach ([
            ['#(?:^|/)(?:\.env|\.git(?:/|$)|phpinfo\.php|server-status|composer\.json|vendor(?:/|$)|backup\.zip|database\.sql)#i', 'sensitive_file_probe', 'high'],
            ['#(?:wp-admin|wp-login|xmlrpc\.php|wp-content)#i', 'cms_probe', 'medium'],
            ['#(?:\.\./|%2e%2e|%252e)#i', 'path_traversal_probe', 'high'],
            ['#(?:union(?:%20|\+|\s)+select|information_schema|sleep\s*\()#i', 'sql_injection_probe', 'high'],
            ['#(?:<script|%3cscript|javascript:)#i', 'xss_probe', 'high'],
        ] as [$pattern, $type, $risk]) if (preg_match($pattern, $path)) return compact('type', 'risk') + ['signal' => 'path_pattern'];
        if ($status === 429) return ['type' => 'rate_limit', 'risk' => 'medium', 'signal' => 'http_429'];
        if (preg_match('#(?:sqlmap|nikto|masscan|zgrab|nmap)#i', $ua)) return ['type' => 'scanner_user_agent', 'risk' => 'medium', 'signal' => 'user_agent'];
        return null;
    }

    private function isLikelyBot(string $ua): bool { return $ua === '' || (bool) preg_match('#(?:bot|crawler|spider|slurp|curl|wget|headless)#i', $ua); }
    private function device(string $ua): string { return str_contains($ua, 'tablet') || str_contains($ua, 'ipad') ? 'tablet' : (preg_match('#mobile|iphone|android#', $ua) ? 'mobile' : 'desktop'); }
    private function browser(string $ua): string { return match (true) { str_contains($ua, 'edg/') => 'Edge', str_contains($ua, 'firefox/') => 'Firefox', str_contains($ua, 'chrome/') => 'Chrome', str_contains($ua, 'safari/') => 'Safari', default => 'Other' }; }
    private function platform(string $ua): string { return match (true) { str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS', str_contains($ua, 'android') => 'Android', str_contains($ua, 'windows') => 'Windows', str_contains($ua, 'mac os') => 'macOS', str_contains($ua, 'linux') => 'Linux', default => 'Other' }; }
    private function safeCampaign(mixed $value, int $length = 120): ?string { $clean = trim(strip_tags((string) $value)); return $clean === '' ? null : Str::limit($clean, $length, ''); }
    private function source(?string $referrer, string $host): string { if (! $referrer || $referrer === $host) return 'direct'; if (preg_match('#google\.|bing\.|duckduckgo\.#i', $referrer)) return 'organic'; if (preg_match('#instagram\.|facebook\.|linkedin\.|tiktok\.#i', $referrer)) return 'social'; return 'referral'; }
    private function countryCode(Request $request): ?string { $code = strtoupper((string) $request->headers->get('CF-IPCountry')); return preg_match('/^[A-Z]{2}$/', $code) && $code !== 'XX' ? $code : null; }
    private function maskIp(string $ip): string { if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { $parts = explode('.', $ip); return $parts[0].'.'.$parts[1].'.'.$parts[2].'.0'; } if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return implode(':', array_slice(explode(':', $ip), 0, 4)).'::'; return 'unknown'; }
}
