<?php

namespace Firxworx\SlimFormHandler;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class App
{
    /**
     * Instance of the Slim API app.
     *
     * @var \Slim\App
     */
    private $app;

    public function __construct() {

        $app = new \Slim\App;

        /** Enable CORS - respond to OPTIONS requests. */
        $app->options('/{routes:.+}', function ($request, $response, $args) {
          return $response;
        });

        /** Add CORS headers to all responses: allow all origins, support POST+OPTIONS. */
        $app->add(function ($req, $res, $next) {
          $response = $next($req, $res);
          return $response
                  ->withHeader('Access-Control-Allow-Origin', '*')
                  ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept')
                  ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
        });

        /** Debug get request */
        $app->get('/', function (Request $request, Response $response) {
            $response->getBody()->write("Hello World");
            return $response;
        });

        /** Create contact/ group to accept POST requests */
        $app->post('/', function(Request $request, Response $response){

          $body = $this->request->getBody();
          $data = json_decode($body);

          return $response->withJson(['status' => 'success', 'data' => $data]);
          
        });

        /** Universal route for handling 404 resource Not Found (intentionally defined last) */
        $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
            $handler = $this->notFoundHandler;
            return $handler($req, $res);
        });

        $this->app = $app;

    }

    /**
     * Return instance of this app
     *
     * @return \Slim\App
     */
    public function get()
    {
        return $this->app;
    }

}
