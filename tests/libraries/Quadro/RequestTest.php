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
use Quadro\Request as Request;

class RequestMockup Extends Request
{
    protected array $postData = [
        'bogus' => 'bogusValue'
    ];
    protected string $method = Request::METHOD_POST;

}

/**
 * Description of ApplicationTest
 *
 * @author R.Demmenie
 */
class RequestTest extends TestCase
{

    public function test_callStatic()
    {
        $request = Request::getInstance(true);
        $this->assertEquals($request->getMethod(), Request::method());
        $this->assertEquals($request->getPath(), Request::path());
        $this->assertEquals('dummy', Request::query('param1', FILTER_SANITIZE_STRING, 'dummy'));
        $this->assertEquals('dummy', Request::post('param1', FILTER_SANITIZE_STRING, 'dummy'));
        Request::setInstance('/test/path');
        $request = Request::getInstance();

        $this->assertEquals('/test/path', Request::path());
        $this->assertEquals('/test/path', $request->getPath(), 'Must be set to the path in the signature');
    }

    public function test_Signature1()
    {
        $signature = 'Delete https://www.domain.com:81/slug1/slug2?param1=value1&param2=value2';

        Request::setInstance($signature);
        $request = Request::getInstance();

        $this->assertEquals(Request::METHOD_DELETE, $request->getMethod());
        $this->assertEquals(Request::SCHEME_HTTPS, $request->getScheme());
        $this->assertTrue($request->isSecure(), 'isSecure should be true!!');
        $this->assertEquals('www.domain.com', $request->getHost());
        $this->assertEquals(81, $request->getPort());
        $this->assertEquals('/slug1/slug2', $request->getPath());
        $this->assertEquals(['slug1', 'slug2'], $request->getSlugs());
        $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request->getGetData());
        $this->assertEquals(strtolower($signature), strtolower($request->getSignature()));
    }

    public function test_Signature2()
    {
        $signature = 'Delete http://www.domain.com/slug1/slug2?param1=value1&param2=value2';

        Request::setInstance($signature);
        $request = Request::getInstance();

        $this->assertEquals(Request::METHOD_DELETE, $request->getMethod());
        $this->assertFalse($request->isSecure(), 'isSecure should be false');
        $this->assertEquals(Request::SCHEME_HTTP, $request->getScheme());
        $this->assertEquals('www.domain.com', $request->getHost());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('/slug1/slug2', $request->getPath());
        $this->assertEquals(['slug1', 'slug2'], $request->getSlugs());
        $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request->getGetData());
        $this->assertEquals(strtolower($signature), strtolower($request->getSignature()));
        $this->assertEquals('value1', $request->getGetData('param1'));
    }

    public function test_Signature3()
    {
        $signature = '/slug1/slug2?param1=value1&param2=value2';
        Request::setInstance($signature);
        $request = Request::getInstance();
        $this->assertEquals(Request::METHOD_GET, $request->getMethod());
        $this->assertFalse($request->isSecure(), 'isSecure should be false');
        $this->assertEquals(Request::SCHEME_HTTP, $request->getScheme());
        $this->assertEquals('localhost', $request->getHost());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('/slug1/slug2', $request->getPath());
        $this->assertEquals(['slug1','slug2'], $request->getSlugs());
        $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request->getGetData());
        $this->assertEquals('GET HTTP://localhost/slug1/slug2?param1=value1&param2=value2', $request->getSignature());
    }

    public function test_PostData()
    {
        $request = RequestMockup::getInstance(true);
        $this->assertEquals(Request::METHOD_POST, $request->getMethod());
        $this->assertEquals('bogusValue', $request->getPostData('bogus'));
    }
}