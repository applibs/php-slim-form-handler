<?php

use PHPUnit\Framework\TestCase;
use Slim\Http\Environment;            // mock http headers
use Slim\Http\Request;                // mock request object
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\RequestBody;

use Firxworx\SlimFormHandler\App;

class ApiTest extends TestCase
{

  protected $app;

  public function setUp()
  {
    $config = file_get_contents(__DIR__ . '/config/config.test.json');
    $config = json_decode($config);

    $this->app = (new App($config, FALSE))->get();
  }

  private function createJsonPostRequest($requestUri, $data, $addHeader = true) {
    $env = Environment::mock([
      'REQUEST_METHOD' => 'POST',
      'REQUEST_URI'    => $requestUri,
      'CONTENT_TYPE'   => 'application/json',
    ]);
    $req = Request::createFromEnvironment($env);
    $bodyStream = $req->getBody();
    $bodyStream->write($data);
    $bodyStream->rewind();
    $req = $req->withBody($bodyStream);
    if ($addHeader) {
      $req = $req->withHeader('Content-Type', 'application/json');
    }
    return $req;
  }

  public function testGoodPost()
  {

    $data = file_get_contents(__DIR__ . '/data/formdata-good.json');

    $req = $this->createJsonPostRequest('/', $data);
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $this->assertSame(200, $response->getStatusCode());

    // $result = json_decode($response->getBody(), true);

  }

  public function testInvalidJsonBody()
  {

    $data = file_get_contents(__DIR__ . '/data/formdata-invalid.json');
    $req = $this->createJsonPostRequest('/', $data);
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $this->assertSame(400, $response->getStatusCode());

    // note: slim automatically attempts parsing recognized content types incl json
    // it returns - {"error":"Control character error, possibly incorrectly encoded"}'
    // our code's own check also looks for json errors
    // it returns - {"error":"Control character error, possibly incorrectly encoded"}

    $this->assertContains("error", (string)$response->getBody());

  }

  public function testFieldVerification()
  {

    $dataArray = json_decode(file_get_contents(__DIR__ . '/data/formdata-good.json'), TRUE);

    // note on assignment of arrays, php copy behaviour is ok for our case
    $dataMissingField = $dataArray;
    $dataExtraField = $dataArray;

    // test missing field
    unset($dataMissingField['email']);
    $req = $this->createJsonPostRequest('/', json_encode($dataMissingField));
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $this->assertSame($response->getStatusCode(), 406);
    $this->assertSame((string)$response->getBody(), json_encode([
      'error' => 'Unexpected or missing data field(s)'
    ]));

    // test extra field
    $dataExtraField['unexpected_test'] = 'unexpected field value';
    $req = $this->createJsonPostRequest('/', json_encode($dataExtraField));
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $this->assertSame($response->getStatusCode(), 406);
    $this->assertSame((string)$response->getBody(), json_encode([
      'error' => 'Unexpected or missing data field(s)'
    ]));

    // test expected fields
    $req = $this->createJsonPostRequest('/', json_encode($dataArray));
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $this->assertSame($response->getStatusCode(), 200);

  }

  public function testUnsupportedContentTypeHeader()
  {

    $data = file_get_contents(__DIR__ . '/data/formdata-good.json');

    $env = Environment::mock([
      'REQUEST_METHOD' => 'POST',
      'REQUEST_URI'    => '/',
      'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
    ]);
    $req = Request::createFromEnvironment($env);
    $stream = $req->getBody();
    $stream->write($data);
    $stream->rewind();
    $req = $req->withBody($stream);

    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $this->assertSame(415, $response->getStatusCode());

  }

  public function testUnsupportedMethod()
  {

    $env = Environment::mock([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI'    => '/',
        ]);
    $req = Request::createFromEnvironment($env);
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);
    $this->assertSame(405, $response->getStatusCode()); // method not allowed

  }

  public function testCorsHeaders()
  {

    // note headers can have >1 value, so an array is returned from both
    // getHeaders() and getHeader()

    // confirm POST has * allowed origin
    $data = file_get_contents(__DIR__ . '/data/formdata-good.json');
    $req = $this->createJsonPostRequest('/', $data);
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $originHeaders = $response->getHeader('Access-Control-Allow-Origin');
    $this->assertEquals(1, count($originHeaders));
    $this->assertSame('*', $originHeaders[0]);

    // confirm an OPTIONS request returns allow origin * header (test preflight requests)
    $env = Environment::mock([
        'REQUEST_METHOD' => 'OPTIONS',
        ]);
    $req = Request::createFromEnvironment($env)->withParsedBody(null);
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);

    $headers = $response->getHeaders();

    $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
    $this->assertSame('*', $headers['Access-Control-Allow-Origin'][0]);
    $this->assertEquals(1, count($headers['Access-Control-Allow-Origin']));

    $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
    $this->assertSame('Content-Type, Accept', $headers['Access-Control-Allow-Headers'][0], "Wrong CORS Headers");

    $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
    $this->assertSame('POST, OPTIONS', $headers['Access-Control-Allow-Methods'][0], "CORS Allowed Methods not POST,OPTIONS");

    $this->assertSame('*', $originHeaders[0]);

  }

}
