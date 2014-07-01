<?php

namespace ShopwareCli\Services;

use ShopwareCli\Config;
use ShopwareCli\Services\PathProvider\PathProvider;

/**
 *
 *
 * Class GitIdentityEnvironment
 * @package ShopwareCli\Services
 */
class GitIdentityEnvironment
{

    protected $wrapperFileName = 'ssh-as.sh';

    protected $sshAliasTemplate = <<<'EOF'
#!/bin/bash
set -e
set -u

ssh -i $SSH_KEYFILE $@
EOF;
    /**
     * @var PathProvider
     */
    private $pathProvider;
    /**
     * @var \ShopwareCli\Config
     */
    private $config;

    public function __construct(PathProvider $pathProvider, Config $config)
    {
        $this->pathProvider = $pathProvider;
        $this->config = $config;
    }

    /**
     * Will return the path to the custom SSH key. Will return null if no
     * custom key is configured
     *
     * @return null|string
     * @throws \RuntimeException
     */
    private function getCustomKey()
    {
        $packageKey = $this->pathProvider->getCliToolPath() . 'ssh.key';
        if (file_exists($packageKey)) {
            return $packageKey;
        }

        if (isset($config['sshKey'])) {
            $keyPath = $config['sshKey'];
            if (!file_exists($keyPath)) {
                throw new \RuntimeException("Could not find ssh key $keyPath");
            }

            return $keyPath;
        }

        return null;
    }

    /**
     * Return path of the git wrapper file. If it doesn't exist, it will be created
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getGitWrapper()
    {
        $dir = $this->pathProvider->getRuntimeDir() . 'sw-cli-tools/';
        $wrapperFile = $dir . 'ssh-as.sh';

        if (file_exists($wrapperFile) || $this->writeGitSshWrapper($dir)) {
            return $wrapperFile;
        }

        throw new \RuntimeException("Could not create git wrapper file $wrapperFile");
    }

    /**
     * Create git wrapper file
     *
     * @param $dir
     * @return bool
     */
    private function writeGitSshWrapper($dir)
    {
        if (is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($dir . $this->wrapperFileName, $this->sshAliasTemplate);

        return file_exists($dir . $this->wrapperFileName);
    }

    /**
     * Will return an array of SSH_KEYFILE and GIT_SSH if a custom ssh key is configured
     * Else null will be returned
     *
     * @return array|null
     */
    public function getGitEnv()
    {
        if (!$this->getCustomKey()) {
            return null;
        }

        return array(
            'SSH_KEYFILE' => $this->getCustomKey(),
            'GIT_SSH' => $this->getGitWrapper()
        );
    }
}