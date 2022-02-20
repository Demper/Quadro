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

namespace Quadro\Dispatcher;

use Quadro\Dispatcher as BaseDispatcher;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Quadro\Application as Application;
use Quadro\Http\RequestInterface as IRequest;

/**
 * The dispatcher takes an url and tries to find a handler for it in the given folders
 *
 * Example:
 * For the URI the dispatcher
 *
 *   /slug1/slug2/slug3
 *
 * 1 Looks for the file /slug1/slug2/slug3.php
 * 2 Looks for the file /slug1/slug2/index.php
 * 3 Looks for the file /slug1/slug2.php
 * 4 Looks for the file /slug1/index.php
 * 5 Looks for the file /slug1.php
 *
 *
 * @license <http://www.wtfpl.net/about/> WTFPL
 * @package libraries\Quadro
 * @author  Rob <rob@jaribio.nl>
 */
class Files extends BaseDispatcher
{


    /**
     * Files Dispatcher constructor.
     * Sets path and verbose properties
     */
    protected function _initialize_()
    {
        if($this->hasOption('path')) {
            $paths = explode(';', $this->getOption('path'));
            foreach($paths  as $path) {
                $this->addPath($path);
            }
        }
        $this->setVerbose($this->getOption('verbose', false));
    }


    /**
     * One or more directories to search in for a URI handler
     * @var array
     */
    protected array $path = [];

    /**
     * Returns all the folders with URI handlers.
     * @return array
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * Adds a path to look for URI handlers
     *
     * @param string $path
     * @throws Exception
     */
    public function addPath(string $path): void
    {
        $path = realpath($path) . Application::DS;
        if (!isset($this->path[$path])) {
            if (!file_exists($path)) {
                throw new Exception(sprintf('Path not found: %s', $path), 500);
            }
            $this->path[$path] = $path;
        }
    }



    /**
     * Whether to add messages to the Response object explaining how the handler is found
     * @var bool
     */
    protected bool $verbose = false;

    /**
     * Sets the Verbose property
     * @param bool $verbose
     * @return $this
     */
    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * Whether to add messages to the Response object explaining how the handler is found
     * @return bool
     */
    public function getVerbose(): bool
    {
        return $this->verbose;
    }




    /**
     * Looks for the URI handlers for the URI __$requestUri__
     *
     * @see
     * @param IRequest $request
     * @return mixed
     * @throws \Quadro\Config\Exception
     * @throws NotFoundException
     * @throws \Quadro\Exception
     */
    public function handleRequest(IRequest $request): mixed
    {
        $response = Application::getInstance()->getResponse();
        $requestUri = $this->sanitizeRequestUri($request->getPath());

        // Debug info
        if ($this->getVerbose()) {
            $response->addMessage(__METHOD__ . " DEBUG = TRUE");
            $response->addMessage(__METHOD__ . " URI    = $requestUri");
        }

        $file = false;
        foreach ($this->getPath() as $curPath) {

            // the current path
            $curPath = rtrim($curPath, DIRECTORY_SEPARATOR);
            if ($this->getVerbose())
                $response->addMessage(__METHOD__ . " PATH  = $curPath");

            // initialize variables
            $slugs = explode('/', parse_url($requestUri, PHP_URL_PATH));

            // our slug string starts with a separator (see parse_url). The explode() function will there for always add an
            // empty slug as the first item. We do not need that so remove the first item
            array_shift($slugs);

//            // empty items are not welcome either
//            if (in_array('', $slugs)) {
//                if ($this->getVerbose())
//                    $response->addMessage(__METHOD__ . " EMPTY SLUGS = " . implode(' > ', array_reverse($slugs)));
//                return false;
//            }
            if ($this->getVerbose())
                $response->addMessage(__METHOD__ . " SLUGS = " . implode(' > ', array_reverse($slugs)));

            // loop through slugs
            while (count($slugs)) {
                $base = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $slugs);
                $file = $curPath . $base;
                if (str_contains($file, '..')) {
                    return false;
                }
                if (!str_starts_with($file, $curPath)) {
                    return false;
                }
                if ($this->getVerbose())
                    $response->addMessage(__METHOD__ . " START = $file");
                $checks = [
                    $file . '.php',
                    $file . DIRECTORY_SEPARATOR . 'index.php',
                    $file,
                ];
                foreach ($checks as $fileTemp) {
                    if ($this->getVerbose())
                        $response->addMessage("CHECK : $fileTemp");
                    if (file_exists($fileTemp) && !is_dir($fileTemp)) {
                        $file = $fileTemp;
                        break 2;
                    }
                }
                $file = false;
                array_pop($slugs);
            }

            if ($this->getVerbose())
                $response->addMessage(__METHOD__ . " MATCH = $file");

            if (false !== $file) break;
        }

        if (false === $file) {
            throw new NotFoundException(sprintf('URI "%s" Not found', $requestUri), 404);
        }

        ob_start(); // make sure we do not send anything yet
        $result = require($file);
        $content = ob_get_contents();
        ob_end_clean(); // make sure we do not send anything yet

        // content has precedent over the result
        if ('' != $content) return $content;
        return $result;
    }


    #[Pure]
    #[ArrayShape(['path' => "int[]|string[]"])]
    public function jsonSerialize(): array
    {
        return [
            'path' => array_keys($this->getPath())
        ];
    }


} // class
