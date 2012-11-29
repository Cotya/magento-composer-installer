<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class CopyTest extends AbstractTest
{
    /**
     * @param string $dest
     * @param string $src
     * @return Copy
     */
    public function getTestDeployStrategy($dest, $src)
    {
        return new Copy($dest, $src);
    }

    /**
     * @param bool $isDir
     * @return string
     */
    public function getTestDeployStrategyFiletype($isDir = false)
    {
        if ($isDir) return self::TEST_FILETYPE_DIR;

        return self::TEST_FILETYPE_FILE;
    }
}