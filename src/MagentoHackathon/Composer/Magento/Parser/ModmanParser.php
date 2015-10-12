<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Parsers modman files
 */
class ModmanParser implements Parser
{

    /**
     * @var \SplFileObject The modman file
     */
    protected $file;

    /**
     *
     * @param string $modManFile
     */
    public function __construct($modManFile)
    {
        $this->file = new \SplFileObject($modManFile);
    }

    /**
     * @return array
     * @throws \ErrorException
     */
    public function getMappings()
    {
        if (!$this->file->isReadable()) {
            throw new \ErrorException(sprintf('modman file "%s" not readable', $this->file->getPathname()));
        }

        $map = $this->parseMappings();
        return $map;
    }

    /**
     * @throws \ErrorException
     * @return array
     */
    protected function parseMappings()
    {
        $map = array();
        foreach ($this->file as $line => $row) {
            $row = trim($row);
            if ('' === $row || in_array($row[0], array('#', '@'))) {
                continue;
            }
            $parts = preg_split('/\s+/', $row, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) === 1) {
                $part = reset($parts);
                $map[] = array($part, $part);
            } elseif (is_int(count($parts) / 2)) {
                $partCountSplit = count($parts) / 2;
                $map[] = array(
                    implode(' ', array_slice($parts, 0, $partCountSplit)),
                    implode(' ', array_slice($parts, $partCountSplit)),
                );
            } else {
                throw new \ErrorException(
                    sprintf('Invalid row on line %d has %d parts, expected 2', $line, count($parts))
                );
            }
        }
        return $map;
    }
}
