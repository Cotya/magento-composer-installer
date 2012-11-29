<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class LinkTest extends AbstractTest
{
    /**
     * @param $dest
     * @param $src
     * @return Copy
     */
    public function getTestDeployStrategy($dest, $src)
    {
        return new Link($dest, $src);
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