<?php
namespace Mailer;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class indexTest extends TestCase
{

    
    function testPOST()
    {
        
        $client = new Client(['base_uri' => 'http://127.0.0.1:4000']);
        $name = 'Rajat';
        $email = 'dummy@dummy.com';
        $message = "This is a dummy message";


        $data = array(
            'user-name' => $name,
            'email' => $email,
            'message' => $message,
            "submit" => true
        );

        $request = $client->post('index.php', [
            'form_params' => $data
        ]);
        $response = $request->send();
        echo $response;
        $this->assertEquals(201, $response->getStatusCode());
        // $this->assertTrue($response->hasHeader('Location'));
        // $data = json_decode($response->getBody(true), true);
        // $this->assertArrayHasKey('nickname', $data);
    }
}
