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

namespace Algolia\ScoutExtended\Settings;

use Algolia\ScoutExtended\Contracts\LocalSettingsRepositoryContract;
use Algolia\ScoutExtended\Repositories\RemoteSettingsRepository;
use Algolia\ScoutExtended\Repositories\UserDataRepository;

/**
 * @internal
 */
class Synchronizer
{
    /**
     * @var \Algolia\ScoutExtended\Settings\Compiler
     */
    private $compiler;

    /**
     * @var \Algolia\ScoutExtended\Settings\Encrypter
     */
    private $encrypter;

    /**
     * @var \Algolia\ScoutExtended\Contracts\LocalSettingsRepositoryContract
     */
    private $localRepository;

    /**
     * @var \Algolia\ScoutExtended\Repositories\RemoteSettingsRepository
     */
    private $remoteRepository;

    /**
     * @var \Algolia\ScoutExtended\Repositories\UserDataRepository
     */
    private $userDataRepository;

    /**
     * Synchronizer constructor.
     *
     * @param \Algolia\ScoutExtended\Settings\Compiler $compiler
     * @param \Algolia\ScoutExtended\Settings\Encrypter $encrypter
     * @param \Algolia\ScoutExtended\Contracts\LocalSettingsRepositoryContract $localRepository
     * @param \Algolia\ScoutExtended\Repositories\RemoteSettingsRepository $remoteRepository
     * @param \Algolia\ScoutExtended\Repositories\UserDataRepository $userDataRepository
     *
     * @return void
     */
    public function __construct(
        Compiler $compiler,
        Encrypter $encrypter,
        LocalSettingsRepositoryContract $localRepository,
        RemoteSettingsRepository $remoteRepository,
        UserDataRepository $userDataRepository
    ) {
        $this->compiler = $compiler;
        $this->encrypter = $encrypter;
        $this->localRepository = $localRepository;
        $this->remoteRepository = $remoteRepository;
        $this->userDataRepository = $userDataRepository;
    }

    /**
     * Analyses the settings of the given index.
     *
     * @param string $index
     *
     * @return \Algolia\ScoutExtended\Settings\Status
     */
    public function analyse($index): Status
    {
        $remoteSettings = $this->remoteRepository->find($index);

        return new Status($this->localRepository, $this->userDataRepository, $this->encrypter, $remoteSettings, $index);
    }

    /**
     * Downloads the settings of the given index.
     *
     * @param string $index
     *
     * @return void
     */
    public function download($index): void
    {
        $settings = $this->remoteRepository->find($index);

        $path = $this->localRepository->getPath($index);

        $this->compiler->compile($settings, $path);

        $settingsHash = $this->encrypter->encrypt($settings);

        $this->userDataRepository->save($index, ['settingsHash' => $settingsHash]);
    }

    /**
     * Uploads the settings of the given index.
     *
     * @param string $index
     *
     * @return void
     */
    public function upload($index): void
    {
        $settings = $this->localRepository->find($index);

        $settingsHash = $this->encrypter->encrypt($settings);

        $this->userDataRepository->save($index, ['settingsHash' => $settingsHash]);
        $this->remoteRepository->save($index, $settings);
    }
}
