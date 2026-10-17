<?php

namespace App\Support;

use RuntimeException;

/**
 * Customers give us URLs that the server then calls (webhooks, WordPress sites).
 * Refuse URLs that point inside our own network, so nobody can use us to reach
 * 127.0.0.1, the LAN, or cloud metadata addresses.
 */
final class UrlGuard
{
    public static function check(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('Only http(s) URLs are allowed.');
        }
        if (! config('publishers.block_private_hosts')) {
            return;
        }

        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);
        if ($ips === []) {
            throw new RuntimeException("Cannot resolve host {$host}.");
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException("Host {$host} points to a private address; it is not allowed.");
            }
        }
    }

    /** @return list<string> */
    private static function resolve(string $host): array
    {
        $ips = [];
        foreach ((array) @dns_get_record($host, DNS_A | DNS_AAAA) as $r) {
            $ips[] = $r['ip'] ?? $r['ipv6'] ?? null;
        }
        $ips = array_values(array_filter($ips));
        if ($ips === []) {
            $v4 = gethostbyname($host);
            if ($v4 !== $host) {
                $ips[] = $v4;
            }
        }

        return $ips;
    }
}
