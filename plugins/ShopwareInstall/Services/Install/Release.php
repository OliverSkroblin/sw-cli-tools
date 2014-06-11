<?php

namespace Plugin\ShopwareInstall\Services\Install;

use ShopwareCli\Application\Logger;
use ShopwareCli\Config;

use Plugin\ShopwareInstall\Services\ReleaseDownloader;
use Plugin\ShopwareInstall\Services\VcsGenerator;
use Plugin\ShopwareInstall\Services\ConfigWriter;
use Plugin\ShopwareInstall\Services\Database;
use Plugin\ShopwareInstall\Services\Demodata;
/**
 * This install service will run all steps needed to setup shopware in the correct order
 *
 * Class Release
 * @package Plugin\ShopwareInstall\Services\Install
 */
class Release
{
    /** @var Config */
    protected $config;

    /** @var  VcsGenerator */
    protected $vcsGenerator;

    /** @var  ConfigWriter */
    protected $configWriter;

    /** @var  Database */
    protected $database;

    /** @var  Demodata */
    protected $demoData;
    /**
     * @var ReleaseDownloader
     */
    private $releaseDownloader;

    public function __construct(
        ReleaseDownloader $releaseDownloader,
        Config $config,
        VcsGenerator $vcsGenerator,
        ConfigWriter $configWriter,
        Database $database,
        Demodata $demodata

    )
    {
        $this->config = $config;
        $this->vcsGenerator = $vcsGenerator;
        $this->configWriter = $configWriter;
        $this->database = $database;
        $this->releaseDownloader = $releaseDownloader;
    }

    public function installShopware($username, $password, $name, $mail, $language, $release, $installDir, $basePath, $database)
    {
        $this->releaseDownloader->downloadRelease($release, $installDir);
        $this->generateVcsMapping($installDir);
        $this->writeShopwareConfig($installDir, $database);
        $this->setupDatabase($username, $password, $name, $mail, $language, $installDir, $database);

        Logger::info("<info>Install completed</info>");
    }

    /**
     * @param $installDir
     */
    private function generateVcsMapping($installDir)
    {
        $this->vcsGenerator->createVcsMapping($installDir, array_map(function ($repo) {
            return $repo['destination'];
        }, $this->config['ShopwareInstallConfig']['Repos']));
    }

    /**
     * @param $installDir
     * @param $database
     */
    private function writeShopwareConfig($installDir, $database)
    {
        $this->configWriter->writeConfigPhp(
            $installDir,
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['user'],
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['pass'],
            $database,
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['host']
        );
    }

    /**
     * @param $installDir
     * @param $basePath
     * @param $database
     */
    private function writeBuildProperties($installDir, $basePath, $database)
    {
        $this->configWriter->writeBuildProperties(
            $installDir,
            $this->config['ShopwareInstallConfig']['ShopConfig']['host'],
            $basePath,
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['user'],
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['pass'],
            $database,
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['host']
        );
    }

    private function setupDatabase($username, $password, $name, $mail, $language, $installDir, $database)
    {
        $this->database->setup(
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['user'],
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['pass'],
            $database,
            $this->config['ShopwareInstallConfig']['DatabaseConfig']['host']
        );
        $this->database->importReleaseInstallDeltas($installDir);
        $this->database->createAdmin($username, $name, $mail, $language, $password);
    }
}