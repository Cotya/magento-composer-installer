<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class CopyTest extends AbstractTest
{
    /**
     * @param string $src
     * @param string $dest
     * @return Copy
     */
    public function getTestDeployStrategy($src, $dest)
    {
        return new Copy($src, $dest);
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