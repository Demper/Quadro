<?php
/**
 * This file is part of the Quadro RestFull Framework which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
 */
declare(strict_types=1);

namespace Quadro\Resource;

use Quadro\Application as Application;
use Quadro\Application\Component As Component;

class Text extends Component
{

    /**
     * @param string $language
     * @param array<int, string> $directories
     */
    public function __construct(string $language = 'en', array $directories = [])
    {
        $this->setLanguage($language);
        foreach($directories as $directory) {
            $this->addDirectory($directory);
        }

        // always add Quadro translation
        $this->addDirectory(QUADRO_DIR . 'resources' . DIRECTORY_SEPARATOR . 'text' . DIRECTORY_SEPARATOR);
    }

    /**
     * @var array<string, string> $_directories
     */
    protected array $_directories = [];

    /**
     * @return array<string, string>
     */
    public function getDirectories(): array
    {
        return $this->_directories;
    }

    /**
     * @param string $directory
     * @return self
     */
    public function addDirectory(string $directory) : self
    {
        // TODO validate paths
        $this->_directories[$directory] = $directory;
        return $this;
    }


    /**
     * @var string
     */
    protected string $_language = 'en';

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->_language;
    }

    /**
     * @param string $language
     * @return self
     */
    public function setLanguage(string $language): self
    {
        $this->_language = $language;
        return $this;
    }

    /**
     * Default language
     */
    const LANG_DEFAULT = 'en';

    /**
     * @var array<string[]> $_translationCache
     */
    protected array $_translationCache = [];

    /**
     * @param string $collection
     * @param string $text
     * @param array<int, mixed> $placeholders
     * @return string
     */
    public function translate(string $collection, string $text, array $placeholders=[]): string
    {
        if(!isset($this->_translationCache[$collection]) || !isset($this->_translationCache[$collection][$text])) {
            foreach($this->_directories as $dir) {
                $file = $dir . $this->getLanguage() . Application::DS
                    . strtolower((string) preg_replace('/[^0-9a-zA-Z]/', '-', $collection)) . '.php';
                if (file_exists($file)) {
                    $this->_translationCache[$collection] = (array)include $file;
                };
                if (!isset($this->_translationCache[$collection][$text])) {
                    $this->_translationCache[$collection][$text] = $text;
                }
            }
        }
        return vsprintf($this->_translationCache[$collection][$text], $placeholders);
    }

    /**
     * @param string[] $collections
     * @param bool $reload
     * @return int
     */
    public function loadTranslations(array $collections, bool $reload=false): int
    {
        $loaded = 0;
        foreach($collections as $collection){
            if(!isset($this->_translationCache[$collection]) || $reload) {
                foreach($this->_directories as $dir) {
                    $file = $dir . $this->getLanguage() . Application::DS
                        . strtolower((string) preg_replace('/[^0-9a-zA-Z]/', '-', $collection)) . '.php';
                    if (file_exists($file)) {
                        $this->_translationCache[$collection] = (array)include $file;
                        $loaded++;
                    };
                }
            }
        }
        return $loaded;
    }

}