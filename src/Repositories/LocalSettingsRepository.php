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

use Algolia\ScoutExtended\Contracts\LocalSettingsRepositoryContract;
use Algolia\ScoutExtended\Settings\Settings;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * @internal
 */
final class LocalSettingsRepository implements LocalSettingsRepositoryContract
{
    /**
     * @var \Algolia\ScoutExtended\Repositories\RemoteSettingsRepository
     */
    private $remoteRepository;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * LocalRepository constructor.
     *
     * @param \Algolia\ScoutExtended\Repositories\RemoteSettingsRepository $remoteRepository
     * @param \Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(RemoteSettingsRepository $remoteRepository, Filesystem $files)
    {
        $this->remoteRepository = $remoteRepository;
        $this->files = $files;
    }

    /**
     * Checks if the given index settings exists.
     *
     * @param string $index
     *
     * @return bool
     */
    public function exists($index): bool
    {
        return $this->files->exists($this->getPath($index));
    }

    /**
     * Get the settings path of the given index name.
     *
     * @param string $index
     *
     * @return string
     */
    public function getPath($index): string
    {
        $name = str_replace('_', '-', $index);

        $name = is_array($name) ? current($name) : $name;

        $fileName = 'scout-'.Str::lower($name).'.php';
        $settingsPath = config('scout.algolia.settings_path');

        if ($settingsPath === null) {
            return app('path.config').DIRECTORY_SEPARATOR.$fileName;
        }

        if (! $this->files->exists($settingsPath)) {
            $this->files->makeDirectory($settingsPath, 0755, true);
        }

        return $settingsPath.DIRECTORY_SEPARATOR.$fileName;
    }

    /**
     * Find the settings of the given Index.
     *
     * @param $index
     *
     * @return \Algolia\ScoutExtended\Settings\Settings
     */
    public function find($index): Settings
    {
        return new Settings(($this->exists($index) ? require $this->getPath($index) : []),
            $this->remoteRepository->defaults());
    }
}
