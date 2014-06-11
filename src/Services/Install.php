<?php

namespace ShopwareCli\Services;

use ShopwareCli\OutputWriter\OutputWriterInterface;
use ShopwareCli\Struct\Plugin;

/**
 * Checks out a given plugin, activates it and adds it to the phpstorm vcs.xml
 *
 * Class Install
 * @package ShopwareCli\Services
 */
class Install
{
    /** @var \ShopwareCli\Services\Checkout */
    protected $checkout;
    /** @var \ShopwareCli\OutputWriter\OutputWriterInterface */
    protected $writer;

    public function __construct(Checkout $checkout, OutputWriterInterface $writer)
    {
        $this->checkout = $checkout;
        $this->writer = $writer;
    }

    function install(Plugin $plugin, $shopwarePath, $inputActivate = false, $branch = 'master')
    {
        $pluginName = $plugin->name;

        $this->checkout->checkout($plugin, $shopwarePath . '/engine/Shopware/Plugins/Local/', $branch);

        if ($inputActivate) {
            $this->writer->write(exec($shopwarePath . '/bin/console sw:plugin:refresh'));
            $this->writer->write(exec($shopwarePath . '/bin/console sw:plugin:install --activate ' . $pluginName));
        }

        $this->addPluginVcsMapping($plugin, $shopwarePath);

        return;
    }

    function addPluginVcsMapping(Plugin $plugin, $shopwarePath)
    {
        $vcsMappingFile = $shopwarePath . '/.idea/vcs.xml';
        $pluginDestPath = $plugin->module . "/" . $plugin->name;

        if (!file_exists($vcsMappingFile)) {
            return;
        }

        $mapping = file_get_contents($vcsMappingFile);
        $xml = new \SimpleXMLElement($mapping);
        foreach ($xml->component->mapping as $mapping) {
            // if already mapped, return
            if (strpos($this->normalize($mapping['directory']), $this->normalize($pluginDestPath)) !== false) {
                return;
            }
        }

        $mappingDirectory = '$PROJECT_DIR$/engine/Shopware/Plugins/Local/' . $pluginDestPath;

        // mapping needs to be created
        $newMapping = $xml->component->addChild('mapping');
        $newMapping->addAttribute('vcs', 'Git');
        $newMapping->addAttribute('directory', $mappingDirectory);

        $xml->asXML($vcsMappingFile);
    }

    /**
     * Normalize directory strings to make them comparable
     *
     * @param $string
     * @return string
     */
    private function normalize($string)
    {
        return strtolower(str_replace(array('/', '\\'), '-', $string));
    }
}