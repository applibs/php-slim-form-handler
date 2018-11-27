<?php

namespace Firxworx\SlimFormHandler;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class App
{
    /**
     * Instance of the Slim API app.
     * @var \Slim\App
     */
    private $app;

    /**
     * Config settings passed to class (loaded from config.json)
     * @var object
     */
    private $config;

    /**
     * Flag to enable or disable live email sending for dev/testing (default true)
     * @var boolean
     */
    private $mailHot;

    /**
     * Define the API with basic support for CORS, supporting POST + OPTIONS requests.
     *
     * @param object $config
     */
    public function __construct($config, $mailHot = true) {

        $this->config = $config;
        $this->mailHot = $mailHot;

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

        /** Handle POST requests (contact form submissions) */
        $obj = $this;
        $app->post('/', function(Request $request, Response $response) use ($obj){

          $contentType = $request->getContentType();
          if (strpos($contentType, 'application/json') === false) {
            return $response->withStatus(415)   // unsupported media type
                            ->withJson(["error" => "Unsupported media type"]);
          }

          // slim will recognize and attempt to parse bodies w/ json headers

          $body = $this->request->getBody();
          $data = @json_decode($body);

          if (JSON_ERROR_NONE !== json_last_error())
          {
            return $response->withStatus(400)   // bad request
                            ->withJson(["error" => json_last_error_msg()]);
          }

          if (!$obj->verifyContactFields($data))
          {
            return $response->withStatus(406)   // not acceptable
                            ->withJson(["error" => 'Unexpected or missing data field(s)']);
          }

          $emailBody = '';
          foreach ($data as $field => $value) {
              $emailBody .= "$field: " . trim($value) . "\n";
          }

          if ($obj->mailHot) {
            $mail = $obj->sendMail($emailBody);
          } else {
            $mail = [];
            $mail['disabled'] = true;
          }

          if (isset($mail['success']) && $mail['success']) {
            return $response->withJson(['status' => 'success']);
          } elseif (isset($mail['disabled']) && $mail['disabled']) {
            return $response->withJson(['status' => 'disabled']);
          } else {
            return $response->withStatus(500)
                            ->withJson(['status' => 'error', 'error' => $mail['err']]);
          }

        });

        /** Universal route for handling 404 resource Not Found (intentionally defined last) */
        $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
            $handler = $this->notFoundHandler;
            return $handler($req, $res);
        });

        $this->app = $app;

    }

    /**
     * Verify that exactly the expected fields have been sent to the server with
     * no missing or extra fields (re the contact form).
     *
     * @param object $data object as created by json_decode() of request body
     * @return bool
     */
    public function verifyContactFields($data)
    {

      if (!is_object($data))
      {
        return false;
      }

      $fields = get_object_vars($data);
      if (count($fields) !== count($this->config->expectedFields))
      {
          return false;
      }

      foreach ($data as $field => $value)
      {
          if (!in_array($field, $this->config->expectedFields))
          {
            return false;
          }
      }

      return true;

    }

    /**
     * Send plaintext authenticated SMTP email using PHPMailer package to the
     * email address specified in project config.json.
     *
     * @param string $plainText body of the email
     * @return array with 'success'=> true or false, 'err' contains info in error case.
     */
    public function sendMail($plainText)
    {

      $mail = new PHPMailer;

      $mail->isSMTP();                                 // switch to smtp
      $mail->SMTPDebug = 0;                            // 1: errors + messages
      $mail->Host = $this->config->host;
      $mail->SMTPAuth = $this->config->SMTPAuth;
      $mail->SMTPSecure = $this->config->SMTPSecure;
      $mail->Port = $this->config->port;
      $mail->Username = $this->config->username;
      $mail->Password = $this->config->password;

      $mail->From = $this->config->fromEmail;
      $mail->FromName = $this->config->fromName;
      $mail->addAddress($this->config->toEmail);
      $mail->isHTML(false);                            // if true, you can also set altBody
      $mail->Subject = $this->config->subject;
      $mail->Body = $plainText;

      $status = $mail->send();

      if(!$status)
      {
        // do not send back smtp-related debug messages to clients ($mail->ErrorInfo)
        return ["success" => false, "err" => "Error processing form"];
      }
      else
      {
        return ["success" => true];
      }

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
