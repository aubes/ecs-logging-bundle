<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

final class HttpRequestProcessor implements ResetInterface
{
    /** @var null|\WeakReference<Request> */
    private ?\WeakReference $cachedRequest = null;
    /** @var null|array<string, mixed> */
    private ?array $cachedHttpContext = null;
    /** @var null|array<string, mixed> */
    private ?array $cachedUrlContext = null;
    /** @var null|array<string, string>|false */
    private false|array|null $cachedClientContext = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly bool $includeFullUrl = false,
        private readonly bool $includeClientIp = false,
        private readonly bool $includeReferrer = false,
    ) {
    }

    public function reset(): void
    {
        $this->cachedRequest = null;
        $this->cachedHttpContext = null;
        $this->cachedUrlContext = null;
        $this->cachedClientContext = false;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return $record;
        }

        if ($this->cachedRequest?->get() !== $request) {
            $this->cachedRequest = \WeakReference::create($request);
            $this->cachedHttpContext = null;
            $this->cachedUrlContext = null;
            $this->cachedClientContext = false;
        }

        $context = $record->context;

        if (!isset($context['http'])) {
            $this->cachedHttpContext ??= $this->buildHttpContext($request);
            $context['http'] = $this->cachedHttpContext;
        }

        if (!isset($context['url'])) {
            $this->cachedUrlContext ??= $this->buildUrlContext($request);
            $context['url'] = $this->cachedUrlContext;
        }

        if ($this->includeClientIp && !isset($context['client'])) {
            if ($this->cachedClientContext === false) {
                $this->cachedClientContext = $this->buildClientContext($request);
            }

            if ($this->cachedClientContext !== null) {
                $context['client'] = $this->cachedClientContext;
            }
        }

        return $record->with(context: $context);
    }

    /** @return array<string, mixed> */
    private function buildHttpContext(Request $request): array
    {
        $http = [
            'request' => [
                'method' => $request->getMethod(),
            ],
        ];

        $mimeType = $request->headers->get('Content-Type');
        if ($mimeType !== null) {
            $http['request']['mime_type'] = $this->sanitizeString($mimeType, 512);
        }

        $contentLength = $request->headers->get('Content-Length');
        if ($contentLength !== null && (int) $contentLength >= 0) {
            $http['request']['bytes'] = (int) $contentLength;
        }

        if ($this->includeReferrer) {
            $referrer = $request->headers->get('Referer');
            if ($referrer !== null) {
                $http['request']['referrer'] = $this->sanitizeString($referrer, 512);
            }
        }

        // Only the regex capture group ($matches[1], e.g. "1.1") is stored — never the raw
        // SERVER_PROTOCOL value, which can be attacker-influenced in some CGI/proxy configurations.
        $serverProtocol = $request->server->get('SERVER_PROTOCOL');
        if ($serverProtocol !== null && \preg_match('/^HTTP\/([\d.]+)$/', (string) $serverProtocol, $matches)) {
            $http['version'] = $matches[1];
        }

        return $http;
    }

    /** @return array<string, mixed> */
    private function buildUrlContext(Request $request): array
    {
        $scheme = $request->getScheme();
        $url = [
            'path' => $request->getPathInfo(),
            'scheme' => $scheme,
            'domain' => $request->getHost(),
        ];

        if ($this->includeFullUrl) {
            $url['full'] = $this->sanitizeString($request->getUri(), 2048);

            $queryString = $request->getQueryString();
            if ($queryString !== null) {
                $url['query'] = $this->sanitizeString($queryString, 2048);
            }
        }

        $port = $this->resolvePort($request, $scheme);
        if ($port !== null) {
            $url['port'] = $port;
        }

        return $url;
    }

    private function resolvePort(Request $request, string $scheme): ?int
    {
        $port = $request->getPort() !== null ? (int) $request->getPort() : null;
        $isStandardPort = ($port === 80 && $scheme === 'http') || ($port === 443 && $scheme === 'https');

        return ($port !== null && $port > 0 && !$isStandardPort) ? $port : null;
    }

    /**
     * Strips ASCII control characters and enforces a maximum length.
     * Prevents log injection via attacker-controlled header or URL values.
     */
    private function sanitizeString(string $value, int $maxLength): string
    {
        return \substr(\preg_replace('/[\x00-\x1f\x7f]+/', '', $value) ?? '', 0, $maxLength);
    }

    /** @return null|array<string, string> */
    private function buildClientContext(Request $request): ?array
    {
        $clientIp = $request->getClientIp();

        if ($clientIp === null) {
            return null;
        }

        return ['ip' => $clientIp];
    }
}
