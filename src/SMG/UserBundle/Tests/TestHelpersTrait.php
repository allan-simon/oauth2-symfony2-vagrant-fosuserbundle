<?php
namespace SMG\UserBundle\Tests;

trait TestHelpersTrait
{

    private $client;
    private $em;
    private $currentUser;
    private $fixtures;
    private $response;

    protected function performClientRequest(
        $method,
        $urlPath,
        $headers = ['HTTP_ACCEPT' => 'application/json'],
        $rawRequestBody = null
    ) {
        $this->client->request(
            $method,
            $urlPath,
            [],
            [],
            $headers,
            $rawRequestBody
        );

        return $this->client->getResponse();
    }

    private function performAuthenticatedClientRequest($method, $urlPath, $username = null)
    {
        $username = $username ?: $this->authAsUser;
        $this->client = static::createClient(
            array(),
            array('HTTP_Authorization' => "Bearer {$username}")
        );

        return $this->performClientRequest($method, $urlPath);
    }

    protected function assertJsonResponse(
        $response,
        $statusCode = 200,
        $checkValidJson =  true,
        $contentType = 'application/json'
    ) {
        $this->assertEquals(
            $statusCode, $response->getStatusCode(),
            $response->getContent()
        );

        if ($checkValidJson) {
            $this->assertTrue(
                $response->headers->contains('Content-Type', $contentType),
                $response->headers
            );
            $decode = json_decode($response->getContent());
            $this->assertTrue(
                ($decode !== null && $decode !== false),
                'is response valid json: [' . $response->getContent() . ']'
            );
        }
    }

    private function givenLoggedInAs($username)
    {
        $this->currentUser = $username;
        $this->client = static::createClient(
            [],
            [
                'HTTP_Authorization' => "Bearer {$username}",
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
    }

    private function assertPermissionError()
    {
        $this->assertJsonResponse($this->response, 401, false);
    }

    private function assertPermissionDenied()
    {
        $this->assertJsonResponse($this->response, 403, false);
    }

    private function assertNotFoundError()
    {
        $this->assertJsonResponse($this->response, 404, false);
    }

    private function assertNoContentResponse()
    {
        $this->assertJsonResponse($this->response, 204, false);
    }

    private function assertBadRequestError()
    {
        $this->assertJsonResponse($this->response, 400, false);
    }

    private function assertCreatedSuccess()
    {
        $this->assertJsonResponse($this->response, 201, false);
    }

    private function assertOkSuccess()
    {
        $this->assertJsonResponse($this->response, 200, false);
    }

    private function assertAcceptedSuccess()
    {
        $this->assertJsonResponse($this->response, 202, false);
    }

    abstract public function assertTrue($condition, $message = '');
    abstract public function assertEquals(
        $expected,
        $actual,
        $message = '',
        $delta = 0.0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    );
}
