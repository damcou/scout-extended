<?php

declare(strict_types=1);

/**
 * This file is part of Scout Extended.
 *
 * (c) Algolia Team <contact@algolia.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Algolia\ScoutExtended\Repositories;

use Algolia\AlgoliaSearch\Api\SearchClient;
use DateInterval;
use Illuminate\Contracts\Cache\Repository;
use function is_string;

/**
 * @internal
 */
final class ApiKeysRepository
{
    /**
     * Holds the search key.
     */
    private const SEARCH_KEY = 'scout-extended.user-data.search-key';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * @var \Algolia\AlgoliaSearch\Api\SearchClient
     */
    private $client;

    /**
     * ApiKeysRepository constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Algolia\AlgoliaSearch\Api\SearchClient $client
     *
     * @return void
     */
    public function __construct(Repository $cache, SearchClient $client)
    {
        $this->cache = $cache;
        $this->client = $client;
    }

    /**
     * @param  string|object $searchable
     *
     * @return string
     */
    public function getSearchKey($searchable): string
    {
        $searchable = is_string($searchable) ? new $searchable : $searchable;

        $searchableAs = $searchable->searchableAs();

        $securedSearchKey = $this->cache->get(self::SEARCH_KEY.'.'.$searchableAs);

        if ($securedSearchKey === null) {
            $id = config('app.name').'::searchKey';

            $keys = $this->client->listApiKeys()['keys'];

            $searchKey = null;

            foreach ($keys as $key) {
                if (array_key_exists('description', $key) && $key['description'] === $id) {
                    $searchKey = $key['value'];
                }
            }

            $searchKey = $searchKey ?? $this->client->addApiKey(['acl' => ['search']], [
                'description' => config('app.name').'::searchKey',
            ])['key'];

            // Key will be valid for 25 hours.
            $validUntil = time() + (3600 * 25);

            $urlEncodedRestrictions = \Algolia\AlgoliaSearch\Support\Helpers::buildQuery([
                'restrictIndices' => $searchableAs,
                'validUntil' => $validUntil,
            ]);

            $content = hash_hmac('sha256', $urlEncodedRestrictions, $searchKey).$urlEncodedRestrictions;
            $securedSearchKey =  base64_encode($content);

            $this->cache->put(
                self::SEARCH_KEY.'.'.$searchableAs, $securedSearchKey, DateInterval::createFromDateString('24 hours')
            );
        }

        return $securedSearchKey;
    }
}
