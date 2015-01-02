<?php

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Parser class supporting translating path mappings according to
 * the composer.json configuration.
 *
 * Class PathTranslationParser
 * @package MagentoHackathon\Composer\Magento\Parser
 */
class PathTranslationParser implements Parser
{
    /**
     * @var array Variants on each prefix that path mappings are checked
     * against.
     */
    protected $pathPrefixVariants = array('', './');

    /**
     * @var array Path mapping prefixes that need to be translated (i.e. to
     * use a public directory as the web server root).
     */
    protected $pathPrefixTranslations = array();

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * Constructor. Sets the list of path translations to use.
     *
     * @param Parser $parser
     * @param array $translations Path translations
     */
    public function __construct(Parser $parser, array $translations)
    {
        $this->pathPrefixTranslations = $this->createPrefixVariants($translations);
        $this->parser = $parser;
    }

    /**
     * Given an array of path mapping translations, combine them with a list
     * of starting variations. This is so that a translation for 'js' will
     * also match path mappings beginning with './js'.
     *
     * @param $translations
     * @return array
     */
    protected function createPrefixVariants($translations)
    {
        $newTranslations = array();
        foreach ($translations as $key => $value) {
            foreach ($this->pathPrefixVariants as $variant) {
                $newTranslations[$variant . $key] = $value;
            }
        }

        return $newTranslations;
    }

    /**
     * loop the mappings for the wrapped parser, check if any of the targets are for
     * directories that have been moved under the public directory. If so,
     * update the target paths to include 'public/'. As no standard Magento
     * path mappings should ever start with 'public/', and  path mappings
     * that already include the public directory should always have
     * js/skin/media paths starting with 'public/', it should be safe to call
     * multiple times on either.
     *
     * @return array Updated path mappings
     */
    public function getMappings()
    {
        $translatedMappings = array();
        foreach ($this->parser->getMappings() as $index => $mapping) {
            $translatedMappings[$index] = $mapping;
            foreach ($this->pathPrefixTranslations as $prefix => $translate) {
                if (strpos($mapping[1], $prefix) === 0) {
                    // replace the old prefix with the translated version
                    $translatedMappings[$index][1] = $translate . substr($mapping[1], strlen($prefix));
                    // should never need to translate a prefix more than once
                    // per path mapping
                    break;
                }
            }
        }

        return $translatedMappings;
    }
}
