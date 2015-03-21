<?php

namespace SMG\UserBundle\Tests\Controller;

use SMG\UserBundle\Tests\TestHelpersTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class UsersControllerTest extends WebTestCase
{
    use TestHelpersTrait;

    public function setUp()
    {
        $this->client = static::createClient();
        $fixtures = [
            'SMG\UserBundle\DataFixtures\ORM\LoadUserData'
        ];
        $fixtureExecutor = $this->loadFixtures($fixtures);
        $this->em = $fixtureExecutor->getObjectManager();
        $this->fixtures = $fixtureExecutor->getReferenceRepository();
    }

    public function testPostUserCreateNewUserWithEmailUsernamePassword()
    {
        $userPayload = [
            'email' => 'plop@plop.com',
            'username' => 'new_user',
            'plain_password' => 'new_password',
        ];
        $this->performPostUser($userPayload);
        $this->assertUserCreated('new_user');
        $this->assertAcceptedSuccess();
    }


    private function performPostUser(array $userPayload)
    {
        $jsonHeaders = ['CONTENT_TYPE' => 'application/json'];
        $jsonBody = json_encode($userPayload);

        $this->response = $this->performClientRequest(
            'POST',
            '/users',
            $jsonHeaders,
            $jsonBody
        );
    }

    private function assertUserCreated($username)
    {
        $user = $this->em->getRepository('SMGUserBundle:User')->findOneByUsername($username);
        $this->assertNotEquals(null, $user, 'A user should be created');
    }
}
