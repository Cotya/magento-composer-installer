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
     * @return string
     */
    public function getTestDeployStrategyFiletype()
    {
        return AbstractTest::TEST_FILETYPE_FILE;
    }
}