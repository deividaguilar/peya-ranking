<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CustomersControllerTest extends WebTestCase
{
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    public function testCreateCustomers()
    {
        $customers = [
            ['name' => 'Leandro', 'age' => 26],
            ['name' => 'Marcelo', 'age' => 30],
            ['name' => 'Alex', 'age' => 18],
        ];
        $customers = json_encode($customers);

        $this->client->request('POST', '/customers/', [], [], ['CONTENT_TYPE' => 'application/json'], $customers);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
    
    public function testGetCustomers()
    {
        $this->client->request('GET', '/customers/');
        $response = $this->client->getResponse();
        $responseType = $response->headers->contains('Content-Type', 'application/json');
        $this->assertTrue($responseType);
    }
    
    public function testDeleteCustomers()
    {
        $this->client->request('DELETE', '/customers/');
        $response = $this->client->getResponse();
        $this->assertEquals('{"status":"Customers successfully deleted"}', $response->getContent());
    }
}
