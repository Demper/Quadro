<?php
declare(strict_types=1);
/**
 * This file is part of the Digibende Web Framework which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Digibende
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@amstelveen.digibende.nl>
 */

use PHPUnit\Framework\TestCase;

use Quadro\Application as Application;

/**
 * Description of ApplicationTest
 *
 * @author R.Demmenie
 */
class ApplicationTest extends TestCase
{

    public function test_Initialize()
    {
        /**
         * @var Quadro\Response $response
         */
        $response = Application::handleRequest();
        Application::getResponse();

    }

}