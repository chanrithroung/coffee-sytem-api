<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CacheResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $duration = 300): SymfonyResponse
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Don't cache authenticated user-specific data unless explicitly safe
        if ($request->user() && !$this->isCacheSafe($request)) {
            return $next($request);
        }

        // Generate cache key based on request
        $cacheKey = $this->generateCacheKey($request);

        // Check if response is already cached
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            
            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        // Process the request
        $response = $next($request);

        // Only cache successful responses
        if ($response->getStatusCode() === 200 && $this->shouldCache($request, $response)) {
            $cacheData = [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $this->getResponseHeaders($response),
            ];

            Cache::put($cacheKey, $cacheData, $duration);
            
            $response->header('X-Cache', 'MISS')
                    ->header('X-Cache-Key', $cacheKey)
                    ->header('X-Cache-Duration', $duration);
        }

        return $response;
    }

    /**
     * Generate a unique cache key for the request
     */
    private function generateCacheKey(Request $request): string
    {
        $uri = $request->getRequestUri();
        $method = $request->method();
        $query = $request->query();
        
        // Sort query parameters for consistent cache keys
        ksort($query);
        
        // Include user ID for user-specific cacheable routes
        $userId = '';
        if ($request->user() && $this->isUserSpecificCacheable($request)) {
            $userId = ':user:' . $request->user()->id;
        }
        
        return 'api_response:' . md5($method . $uri . serialize($query) . $userId);
    }

    /**
     * Determine if the request is safe to cache for authenticated users
     */
    private function isCacheSafe(Request $request): bool
    {
        // List of routes that are safe to cache even with authentication
        $safeRoutes = [
            '/api/categories',
            '/api/products',
            '/api/categories/*/products',
            '/api/products/low-stock',
        ];

        foreach ($safeRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if user-specific caching is allowed
     */
    private function isUserSpecificCacheable(Request $request): bool
    {
        // Routes that can be cached per user
        $userCacheableRoutes = [
            '/api/user/profile',
            '/api/user/preferences',
        ];

        foreach ($userCacheableRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the response should be cached
     */
    private function shouldCache(Request $request, SymfonyResponse $response): bool
    {
        // Don't cache if response contains cache-control headers indicating no-cache
        $cacheControl = $response->headers->get('Cache-Control');
        if ($cacheControl && (
            str_contains($cacheControl, 'no-cache') || 
            str_contains($cacheControl, 'no-store') ||
            str_contains($cacheControl, 'private')
        )) {
            return false;
        }

        // Don't cache error responses
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // Don't cache empty responses
        if (empty($response->getContent())) {
            return false;
        }

        return true;
    }

    /**
     * Get headers to include in cached response
     */
    private function getResponseHeaders(SymfonyResponse $response): array
    {
        $allowedHeaders = [
            'Content-Type',
            'Content-Encoding',
            'Content-Language',
            'Last-Modified',
            'ETag',
            'Expires',
            'Cache-Control',
        ];

        $headers = [];
        foreach ($allowedHeaders as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }

        return $headers;
    }
}