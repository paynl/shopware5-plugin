<?php

namespace PaynlPayment\Helpers;

class ComposerHelper
{
    /**
     * @param string $defaultValue
     * @return string
     */
    public function getPluginVersion($defaultValue = '')
    {
        $composerFilePath = sprintf('%s/%s', rtrim(__DIR__, '/'), '../composer.json');
        if (file_exists($composerFilePath)) {
            $composer = json_decode(file_get_contents($composerFilePath), true);

            return $composer['version'] ?? $defaultValue;
        }

        return $defaultValue;
    }
}
