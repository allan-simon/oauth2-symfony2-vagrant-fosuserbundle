<?php

namespace SMG\ManagerBundle\Tests\Controller;

use SMG\UserBundle\Entity\User;
use SMG\OauthBundle\Entity\Client;
use SMG\UserBundle\Tests\TestHelpersTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class ManagersControllerTest extends WebTestCase
{
    const ADMIN_USER_NAME = 'admin';
    const ADMIN_USER_PW = 'admin';
    const COMMON_USER_NAME = 'Bobthesponge';
    const COMMON_USER_PW = 'plop';

    use TestHelpersTrait;

    private $user;
    private $oauthClient;

    public function setUp()
    {
        $this->client = static::createClient();
        $fixtures = [
            'SMG\UserBundle\DataFixtures\ORM\LoadUserData',
            'SMG\UserBundle\DataFixtures\ORM\LoadClientData',
        ];
        $fixtureExecutor = $this->loadFixtures($fixtures);
        $this->em = $fixtureExecutor->getObjectManager();
        $this->fixtures = $fixtureExecutor->getReferenceRepository();
    }

    // Tests for PATCH /admin/users/{id}/password

    public function testPatchUserPasswordAsAdminUpdatePassword()
    {
        $this->givenUser('new-user');
        $this->givenClient('new-client');

        $this->loginClientAsAdmin();

        $payload = [
            'new_password' => 'toto',
        ];

        $this->performPatchUser(
            '/password',
            $payload
        );
        $this->assertNoContentResponse();
    }

    public function testPatchUserPasswordAsCommonUserReturn403()
    {
        $this->givenUser('new-user');
        $this->givenClient('new-client');

        $this->loginAsCommonUser();

        $payload = [
            'new_password' => 'toto',
        ];

        $this->performPatchUser(
            '/password',
            $payload
        );
        $this->assertPermissionDenied();
    }

    // conveniency methods

    private function performPatchUser($endpoint, array $userPayload)
    {
        $this->performJsonClientRequest(
            'PUT',
            '/admin/users/'.$this->user->getId().$endpoint,
            $userPayload
        );
    }

    private function performJsonClientRequest(
        $method,
        $endpoint,
        $payload
    ) {
        $jsonHeaders = ['CONTENT_TYPE' => 'application/json'];
        $jsonBody = json_encode($payload);

        $this->response = $this->performClientRequest(
            $method,
            $endpoint,
            $jsonHeaders,
            $jsonBody
        );
    }

    private function loginClientAsAdmin()
    {
        $link = '/oauth/v2/token?'.
            'client_id='.$this->oauthClient->getPublicId().
            '&client_secret='.$this->oauthClient->getSecret().
            '&grant_type=password'.
            '&username='.self::ADMIN_USER_NAME.
            '&password='.self::ADMIN_USER_PW
        ;

        $this->createLoginClient($link);
    }

    private function loginAsCommonUser()
    {
        $link = '/oauth/v2/token?'.
            'client_id='.$this->oauthClient->getPublicId().
            '&client_secret='.$this->oauthClient->getSecret().
            '&grant_type=password'.
            '&username='.self::COMMON_USER_NAME.
            '&password='.self::COMMON_USER_PW
        ;

        $this->createLoginClient($link);
    }

    private function createLoginClient($link)
    {
        $this->client->request('GET', $link);

        $response = $this->client->getResponse()->getContent();

        $decode = json_decode($response, true);

        $this->client = static::createClient(
            array(),
            array('HTTP_Authorization' => "Bearer {$decode['access_token']}")
        );
    }

    private function givenUser($fixtureName)
    {
        $this->user = $this->fixtures->getReference($fixtureName);
    }

    private function givenClient($fixtureName)
    {
        $this->oauthClient = $this->fixtures->getReference($fixtureName);
    }
}
