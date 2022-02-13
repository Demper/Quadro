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

/**
 * Description of ApplicationTest
 *
 * @author R.Demmenie
 */
class QuadroTest extends TestCase
{

    public function test_ApplicationGetInstance()
    {
        $app1 = Quadro\Application::getInstance();
        $app2 = Quadro\Application::getInstance();
        $this->assertEquals($app1, $app2, "Should be the same objects");
    }

    public function test_ApplicationSetOption()
    {
        $app = Quadro\Application::getInstance();
        $app->setOption('dispatcher.path', 'bla bla');
        $this->assertEquals('bla bla', $app->getOption('dispatcher.path'));
        $this->assertEquals('bla bla', $app->getOption('dispatcher')['path']);

        $app->setOption('dispatcher.path', 'ha ha ha');
        $app->setOption('dispatcher.path', 'hihihi');
    }


} // class
