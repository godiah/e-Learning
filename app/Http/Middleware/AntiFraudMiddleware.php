<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\Response;

class AntiFraudMiddleware
{
    protected $blockDuration = 3600;
    protected $maxClicksPerMinute = 5; 
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Check if already blocked
        if (Cache::get("fraud_block:$ip")) {
            Log::warning("Blocked request from: $ip due to previous fraud detection.");
            return response()->json(['message' => 'Suspicious activity detected'], 403);
        }

        // Perform fraud detection checks
        $fraudDetected = $this->checkEmptyUserAgent($userAgent)
            || $this->detectCrawler($userAgent)
            || $this->checkClickVelocity($ip)            
            || $this->checkAbnormalUserAgent($userAgent);
            // || $this->checkSuspiciousIp($ip)
            // || $this->checkGeoBlock($ip);

        if ($fraudDetected) {
            Cache::put("fraud_block:$ip", true, $this->blockDuration);
            Log::alert("Fraud detected from IP: $ip - Blocking access.");
            return response()->json(['message' => 'Suspicious activity detected'], 403);
        }

        return $next($request);
    }

    protected function checkEmptyUserAgent($userAgent): bool
    {
        return empty($userAgent);
    }

    protected function detectCrawler($userAgent): bool
    {
        return (new CrawlerDetect())->isCrawler($userAgent);
    }

    protected function checkClickVelocity($ip): bool
    {
        $key = "click_count:$ip";
        $clicks = Cache::increment($key, 1);
        Cache::put($key, $clicks, now()->addMinutes(1));

        if ($clicks > $this->maxClicksPerMinute) {
            Log::warning("Excessive clicks detected from IP: $ip");
            return true;
        }

        return false;
    }

    protected function checkAbnormalUserAgent($userAgent): bool
    {
        $allowedAgents = ['Mozilla/5.0', 'Chrome/', 'Safari/', 'Edge/'];
    
        foreach ($allowedAgents as $allowed) {
            if (strpos($userAgent, $allowed) !== false) {
                return false; // Allow if matches common browser user agent
            }
        }
        $suspiciousPatterns = [
            '/^curl\/[0-9.]+$/i',       // Curl requests
            '/^python-requests\/[0-9.]+$/i', // Python requests
            '/^libwww-perl/',           // Perl user agents
            '/^Go-http-client/',        // Go http clients
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                Log::warning("Abnormal User-Agent detected: $userAgent");
                return true;
            }
        }

        return false;
    }

    // protected function checkSuspiciousIp($ip): bool
    // {
    //     $location = Location::get($ip);
        
    //     if (!$location) return false;

    //     return $this->isTorExitNode($ip) || $this->isHostingProvider($location);
    // }

    // protected function checkGeoBlock($ip): bool
    // {
    //     $location = Location::get($ip);

    //     if ($location && in_array($location->countryCode, config('antifraud.blocked_countries', []))) {
    //         Log::warning("Access blocked from restricted country: {$location->countryCode} - IP: $ip");
    //         return true;
    //     }

    //     return false;
    // }

    protected function isTorExitNode($ip): bool
    {
        try {
            $exitNodes = file('https://check.torproject.org/torbulkexitlist', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return in_array($ip, $exitNodes);
        } catch (\Exception $e) {
            Log::error("Failed to fetch Tor exit node list: " . $e->getMessage());
            return false;
        }
    }

    protected function isHostingProvider($location): bool
    {
        return $location->hosting ?? false;
    }
}
