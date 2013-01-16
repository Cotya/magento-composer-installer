<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class LinkTest extends AbstractTest
{
    /**
     * @param $src
     * @param $dest
     * @return Link
     */
    public function getTestDeployStrategy($src, $dest)
    {
        return new Link($src, $dest);
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