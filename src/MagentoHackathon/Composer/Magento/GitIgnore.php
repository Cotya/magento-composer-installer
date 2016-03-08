<?php

namespace MagentoHackathon\Composer\Magento;

/**
 * Class GitIgnore
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnore
{
    /**
     * @var array
     */
    protected $lines = array();

    /**
     * @var string|null
     */
    protected $gitIgnoreLocation;

    /**
     * @var bool
     */
    protected $hasChanges = false;

    /**
     * @param string $fileLocation
     */
    public function __construct($fileLocation)
    {
        $this->gitIgnoreLocation = $fileLocation;
        if (file_exists($fileLocation)) {
            $this->lines = $this->removeDuplicates(file($fileLocation, FILE_IGNORE_NEW_LINES));
        }
    }

    /**
     * @param string $file
     */
    public function addEntry($file)
    {
        $file = $this->prependSlashIfNotExist($file);
        if (!in_array($file, $this->lines)) {
            $this->lines[] = $file;
        }
        $this->hasChanges = true;
    }

    /**
     * @param array $files
     */
    public function addMultipleEntries(array $files)
    {
        foreach ($files as $file) {
            $this->addEntry($file);
        }
    }

    /**
     * @param string $file
     */
    public function removeEntry($file)
    {
        $file = $this->prependSlashIfNotExist($file);
        $key = array_search($file, $this->lines);
        if (false !== $key) {
            unset($this->lines[$key]);
            $this->hasChanges = true;

            // renumber array
            $this->lines = array_values($this->lines);
        }
    }

    /**
     * @param array $files
     */
    public function removeMultipleEntries(array $files)
    {
        foreach ($files as $file) {
            $this->removeEntry($file);
        }
    }

    /**
     * @return array
     */
    public function getEntries()
    {
        return $this->lines;
    }

    /**
     * Write the file
     */
    public function write()
    {
        if ($this->hasChanges) {
            file_put_contents($this->gitIgnoreLocation, implode("\n", $this->lines));
        }
    }

    /**
     * Prepend a forward slash to a path
     * if it does not already start with one.
     *
     * @param string $file
     * @return string
     */
    private function prependSlashIfNotExist($file)
    {
        return sprintf('/%s', ltrim($file, '/'));
    }

    /**
     * Removes duplicate patterns from the input array, without touching comments, line breaks etc.
     * Will remove the last duplicate pattern.
     *
     * @param array $lines
     * @return array
     */
    private function removeDuplicates($lines)
    {
        // remove empty lines
        $duplicates = array_filter($lines);

        // remove comments
        $duplicates = array_filter($duplicates, function ($line) {
            return strpos($line, '#') !== 0;
        });

        // check if duplicates exist
        if (count($duplicates) !== count(array_unique($duplicates))) {
            $duplicates = array_filter(array_count_values($duplicates), function ($count) {
                return $count > 1;
            });

            // search from bottom to top
            $lines = array_reverse($lines);
            foreach ($duplicates as $duplicate => $count) {
                // remove all duplicates, except the first one
                for ($i = 1; $i < $count; $i++) {
                    $key = array_search($duplicate, $lines);
                    unset($lines[$key]);
                }
            }

            // restore original order
            $lines = array_values(array_reverse($lines));
        }

        return $lines;
    }
}
