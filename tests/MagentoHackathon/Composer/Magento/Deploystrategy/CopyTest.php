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
     * @return string
     */
    public function getTestDeployStrategyFiletype()
    {
        return AbstractTest::TEST_FILETYPE_FILE;
    }
}