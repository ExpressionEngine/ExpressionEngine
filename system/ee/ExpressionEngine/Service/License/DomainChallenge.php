<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @license https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\License;

use RuntimeException;

class DomainChallenge
{
    private const TTL = 180;
    private const ID_PATTERN = '/\A[a-f0-9]{32}\z/';
    private const PROOF_PATTERN = '/\A[a-f0-9]{64}\z/';

    private $cache;
    private $siteId;
    private $actionUrl;

    /**
     * @param object $cache The site's configured cache, or its file cache when caching is disabled.
     * @param int $siteId
     * @param callable $actionUrl Resolves the installed action only when publishing a proof.
     */
    public function __construct($cache, int $siteId, callable $actionUrl)
    {
        $this->cache = $cache;
        $this->siteId = $siteId;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Create a site-initiated challenge. The ping carries the digest, not the proof served by the action.
     *
     * @return array{id: string, callback_url: string, proof_sha256: string}
     * @throws \Exception If secure randomness or publication is unavailable.
     */
    public function create(): array
    {
        // Whether the URL is on the site's own HTTPS origin is for the caller to judge
        $url = ($this->actionUrl)();
        if ($this->siteId < 1 || !is_string($url)
            || !preg_match('/\AACT=[1-9][0-9]*\z/', (string) parse_url($url, PHP_URL_QUERY))) {
            throw new RuntimeException('The site has no registered domain challenge action.');
        }

        $id = bin2hex(random_bytes(16));
        $proof = bin2hex(random_bytes(32));
        if (!$this->cache->save($this->key($id), $proof, self::TTL) || $this->read($id) !== $proof) {
            throw new RuntimeException('The domain challenge could not be cached.');
        }

        return ['id' => $id, 'callback_url' => $url . '&challenge=' . $id, 'proof_sha256' => hash('sha256', $proof)];
    }

    /**
     * Return a live proof without consuming it; verification may need to retry the GET.
     *
     * @param string $id
     * @return string|null
     */
    public function read(string $id): ?string
    {
        if ($this->siteId < 1 || !preg_match(self::ID_PATTERN, $id)) {
            return null;
        }
        $proof = $this->cache->get($this->key($id));

        return is_string($proof) && preg_match(self::PROOF_PATTERN, $proof) ? $proof : null;
    }

    /**
     * Remove this challenge after verification. An expired or already removed ID needs no cleanup.
     *
     * @param string $id
     * @return void
     * @throws RuntimeException If a live entry cannot be removed.
     */
    public function forget(string $id): void
    {
        if ($this->siteId < 1 || !preg_match(self::ID_PATTERN, $id)) {
            return;
        }
        $key = $this->key($id);
        if (!$this->cache->delete($key) && $this->cache->get($key) !== false) {
            throw new RuntimeException('The domain challenge could not be removed.');
        }
    }

    /**
     * @param string $id
     * @return string
     */
    private function key(string $id): string
    {
        return '/license/domain-challenges/' . $this->siteId . '/' . $id;
    }
}
