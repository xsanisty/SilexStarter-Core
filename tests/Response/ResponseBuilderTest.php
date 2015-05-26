<?php

namespace SilexStarter\Response;

use Twig_Loader_Array;
use Twig_Environment;

class ResponseBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $responseBuilder;

    public function setUp()
    {
        $twig = $this->getMock('Twig_Environment');

        $twig->method('render')
            ->will(
                $this->returnCallback(
                    function ($file, $data) {
                        return $file.': hello '.$data['name'];
                    }
                )
            );

        $this->responseBuilder = new ResponseBuilder($twig);
    }

    public function tearDown()
    {
        $this->responseBuilder = null;
    }

    public function test_response_make()
    {
        $response = $this->responseBuilder->make('some response text');

        assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        assertEquals('some response text', $response->getContent());
        assertSame(200, $response->getStatusCode());
    }

    public function test_response_view()
    {
        $response = $this->responseBuilder->view('index', ['name' => 'stranger']);

        assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        assertEquals('index.twig: hello stranger', $response->getContent());
        assertSame(200, $response->getStatusCode());
    }

    public function test_response_json()
    {
        $response = $this->responseBuilder->json(['test' => 'value']);

        assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        assertJsonStringEqualsJsonString('{"test":"value"}', $response->getContent());
    }

    public function test_response_json_with_arrayable()
    {
        $data = $this->getMock('Illuminate\Contracts\Support\Arrayable');

        $data->method('toArray')
             ->willReturn(['test' => 'value']);

        $response = $this->responseBuilder->json($data);

        assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        assertJsonStringEqualsJsonString('{"test":"value"}', $response->getContent());
    }

    public function test_response_jsonp()
    {
        $response = $this->responseBuilder->jsonp('callback', ['test' => 'value']);

        assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        assertEquals('/**/callback({"test":"value"});', $response->getContent());
    }

    public function test_redirect_response()
    {
        $response = $this->responseBuilder->redirect('http://www.example.com');

        assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        assertEquals(302, $response->getStatusCode());
        assertSame('http://www.example.com', $response->getTargetUrl());
    }

    public function test_stream_response()
    {
        $response = $this->responseBuilder->stream(
            function () {
                return 'test';
            }
        );

        assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $response);
    }

    public function test_binary_response_with_attachment()
    {
        $response = $this->responseBuilder->file(__DIR__.'/../stubs/test_config/sample.php', 'sample.php');

        assertInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse', $response);
    }

    public function test_binary_response()
    {
        $response = $this->responseBuilder->file(__DIR__.'/../stubs/test_config/sample.php');

        assertInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse', $response);
    }
}
