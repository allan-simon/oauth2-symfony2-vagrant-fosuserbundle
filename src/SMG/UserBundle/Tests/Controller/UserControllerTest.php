<?php

namespace SMG\UserBundle\Tests\Controller;

use SMG\UserBundle\Tests\TestHelpersTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class UsersControllerTest extends WebTestCase
{
    use TestHelpersTrait;

    private $user;

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

    // Tests for POST /users

    public function testPostUserCreateNewUserWithEmailUsernamePassword()
    {
        $userPayload = [
            'email' => 'plop@plop.com',
            'username' => 'new_user',
            'plain_password' => 'new_password',
        ];
        $this->performPostUser($userPayload);
        $this->assertAcceptedSuccess();
        $this->assertUserCreated('new_user');
    }

    public function testPostUserCreateNewUserWithoutUsernameFail()
    {
        $userPayload = [
            'email' => 'plop@plop.com',
            'plain_password' => 'new_password',
        ];
        $this->performPostUser($userPayload);
        $this->assertBadRequestError();
        $this->assertUserNotCreated('new_user');
    }

    public function testPostUserCreateNewUserNormalizePhoneNumber()
    {
        $userPayload = [
            'username' => 'new_user',
            'phone_number' => '+4212345',
            'plain_password' => 'new_password',
        ];
        $this->performPostUser($userPayload);
        $this->assertAcceptedSuccess();
        $this->assertUserCreated('new_user');
        $this->assertPhoneEquals('new_user', '004212345');
    }

    // Tests for PATCH /users/{id}/password

    public function testPatchUserPasswordUpdatePassword()
    {
        $payload = [
            'old_password' => 'plop',
            'new_password' => 'toto'
        ];
        $this->givenUser('new-user');
        $this->performPatchUser(
            '/password',
            $payload
        );
        $this->assertNoContentResponse();
    }

    public function testPatchUserPasswordWrongOldPasswordGetForbidden()
    {
        $payload = [
            'old_password' => 'wrong_password',
            'new_password' => 'toto'
        ];
        $this->givenUser('new-user');
        $this->performPatchUser(
            '/password',
            $payload
        );
        $this->assertPermissionDenied();
    }


    // conveniency methods

    private function performPostUser(array $userPayload)
    {
        $this->performJsonClientRequest(
            'POST',
            '/users',
            $userPayload
        );
    }

    private function performPatchUser($endpoint, array $userPayload)
    {
        $this->performJsonClientRequest(
            'PATCH',
            '/users/'.$this->user->getId().$endpoint,
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

    private function findUserByUsername($username)
    {
        return $this->em->getRepository('SMGUserBundle:User')->findOneByUsername($username);
    }

    private function assertPhoneEquals($username, $phone)
    {
        $user = $this->findUserByUsername($username);
        $this->assertEquals(
            $phone,
            $user->getPhoneNumber(),
            'Phone should get normalized'
        );
    }

    private function givenUser($fixtureName)
    {
        $this->user = $this->fixtures->getReference($fixtureName);
    }

    private function assertUserCreated($username)
    {
        $user = $this->findUserByUsername($username);
        $this->assertNotEquals(null, $user, 'A user should be created');
    }

    private function assertUserNotCreated($username)
    {
        $user = $this->findUserByUsername($username);
        $this->assertEquals(null, $user, 'A user should not be created');
    }
}
