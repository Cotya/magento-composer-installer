<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class CopyTest extends AbstractTest
{
    /**
     * @param $dest
     * @param $src
     * @return Copy
     */
    public function getTestDeployStrategy($dest, $src)
    {
        return new Copy($dest, $src);
    }
}