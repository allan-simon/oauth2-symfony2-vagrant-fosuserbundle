<?php

namespace SMG\UserBundle\Tests\Controller;

use SMG\UserBundle\Tests\TestHelpersTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class UsersControllerTest extends WebTestCase
{
    use TestHelpersTrait;

    private $user;
    private $userOriginalEmail;
    private $userOriginalPhoneNumber;

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
        $this->assertUserWithNameHasPhoneEquals('new_user', '004212345');
    }

    public function testPost2UsersWithSameEmailFail()
    {
        $userPayload = [
            'email' => 'plop@plop.com',
            'username' => 'new_user',
            'plain_password' => 'new_password',
        ];
        $this->performPostUser($userPayload);
        $this->assertAcceptedSuccess();
        $this->assertUserCreated('new_user');

        $secondUserPayload = [
            'email' =>  $userPayload['email'],
            'username' => 'new_user_2',
            'plain_password' => 'new_password_2',
        ];

        $this->performPostUser($secondUserPayload);
        $this->assertBadRequestError();
        $this->assertUserNotCreated('new_user_2');
    }

    public function testPost2UsersWithSamePhoneFail()
    {
        $userPayload = [
            'phone_number' => '1234567',
            'username' => 'new_user',
            'plain_password' => 'new_password',
        ];
        $this->performPostUser($userPayload);
        $this->assertAcceptedSuccess();
        $this->assertUserCreated('new_user');

        $secondUserPayload = [
            'phone_number' =>  $userPayload['phone_number'],
            'username' => 'new_user_2',
            'plain_password' => 'new_password_2',
        ];

        $this->performPostUser($secondUserPayload);
        $this->assertBadRequestError();
        $this->assertUserNotCreated('new_user_2');
    }

    public function testPost2UsersWithSameUsernameFail()
    {
        $userPayload = [
            'phone_number' => '1234567',
            'username' => 'new_user',
            'plain_password' => 'new_password',
        ];
        $this->performPostUser($userPayload);
        $this->assertAcceptedSuccess();
        $this->assertUserCreated('new_user');

        $secondUserPayload = [
            'phone_number' =>  '432434243',
            'username' => 'New_user',
            'plain_password' => 'new_password_2',
        ];

        $this->performPostUser($secondUserPayload);
        $this->assertBadRequestError();
        $this->assertUserNotCreated('New_user');
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

    // Tests for PATCH /users/{}/request-change-contact-info

    public function testPatchUserRequestChangeInfoWithEmailSetConfirmationTokenButDoesNotChangeEmailOrPhoneNumber()
    {
        $this->givenUser('user-without-confirmation-token');
        $this->performPatchUser(
            '/request-change-contact-info',
            ['new_contact_info' => 'newemail@example.com']
        );
        $this->assertNoContentResponse();
        $this->assertEmailEquals($this->userOriginalEmail);
        $this->assertPhoneEquals($this->userOriginalPhoneNumber);
        $this->assertUserHasConfirmationTokenSet();
    }

    public function testPatchUserRequestChangeInfoWithPhoneNumberSetConfirmationTokenButDoesNotChangeEmailOrPhoneNumber()
    {
        $this->givenUser('user-without-confirmation-token');
        $this->performPatchUser(
            '/request-change-contact-info',
            ['new_contact_info' => '7891234']
        );
        $this->assertNoContentResponse();
        $this->assertEmailEquals($this->userOriginalEmail);
        $this->assertPhoneEquals($this->userOriginalPhoneNumber);
        $this->assertUserHasConfirmationTokenSet();
    }

    public function testPatchUserRequestChangeInfoWithInvalidInputGiveBadRequest()
    {
        $this->givenUser('user-without-confirmation-token');
        $this->performPatchUser(
            '/request-change-contact-info',
            ['new_contact_info' => 'wrong input']
        );
        $this->assertBadRequestError();
        $this->assertUserHasNoConfirmationTokenSet();
    }

    // Tests for PATCH /users/{}/contact-info

    public function testPatchUserContactInfoWithValidEmailChangeUserEmail()
    {
        $newEmail = "newemail@example.com";

        $this->givenUser('user-with-confirmation-token');
        $this->performPatchUser(
            '/contact-info',
            [
                'new_contact_info' => $newEmail,
                'validation_code' => $this->user->getConfirmationToken(),
            ]
        );
        $this->assertNoContentResponse();
        $this->assertEmailEquals($newEmail);
        $this->assertPhoneEquals($this->userOriginalPhoneNumber);
    }

    public function testPatchUserContactInfoWithValidPhoneChangeUserPhone()
    {
        $newPhone = "789789";

        $this->givenUser('user-with-confirmation-token');
        $this->performPatchUser(
            '/contact-info',
            [
                'new_contact_info' => $newPhone,
                'validation_code' => $this->user->getConfirmationToken(),
            ]
        );
        $this->assertNoContentResponse();
        $this->assertEmailEquals($this->userOriginalEmail);
        $this->assertPhoneEquals($newPhone);
    }

    public function testPatchUserContactInfoWithInvalidValidationCodeBadRequest()
    {
        $newPhone = "789789";

        $this->givenUser('user-with-confirmation-token');
        $this->performPatchUser(
            '/contact-info',
            [
                'new_contact_info' => $newPhone,
                'validation_code' => $this->user->getConfirmationToken().'bad',
            ]
        );
        $this->assertBadRequestError();
        $this->assertEmailEquals($this->userOriginalEmail);
        $this->assertPhoneEquals($this->userOriginalPhoneNumber);
    }

    public function testPatchUserContactInfoWithInvalidInfoBadRequest()
    {
        $this->givenUser('user-with-confirmation-token');
        $this->performPatchUser(
            '/contact-info',
            [
                'new_contact_info' => 'invalid data',
                'validation_code' => $this->user->getConfirmationToken(),
            ]
        );
        $this->assertBadRequestError();
        $this->assertEmailEquals($this->userOriginalEmail);
        $this->assertPhoneEquals($this->userOriginalPhoneNumber);
    }

    // Tests for POST /users/forgot-password

    public function testPostForgotPasswordSetConfirmationCode()
    {
        $this->givenUser('user-without-confirmation-token');
        $this->performPostUserForgotPassword(
            ['contact_info' => $this->user->getEmail()]
        );
        $this->assertOkSuccess();
        $this->assertJsonResponse($this->response);
        $this->assertUserHasConfirmationTokenSet();
    }

    public function testPostForgotPasswordNotFoundIfNobodyWithEmail()
    {
        $this->performPostUserForgotPassword(
            ['contact_info' => 'nobody@example.com']
        );
        $this->assertNotFoundError();
    }

    // Tests for Patch /users/{}/password
    public function testPatchUserPasswordChangePassword()
    {
        $this->givenUser('user-with-confirmation-token');
        $this->performPatchUser(
            '/reset-password',
            [
                'new_password' => 'newpassword',
                'validation_code' => $this->user->getConfirmationToken(),
            ]
        );
        $this->assertNoContentResponse();
    }

    public function testPatchUserPasswordWithWrongCodeReturnsBadRequest()
    {
        $this->givenUser('user-with-confirmation-token');
        $this->performPatchUser(
            '/reset-password',
            [
                'new_password' => 'newpassword',
                'validation_code' => $this->user->getConfirmationToken().'bad',
            ]
        );
        $this->assertBadRequestError();
    }

    // tests PUT /users{}/confirmation-token/{}

    public function testPutUserConfirmationTokenEnableUser()
    {
        $this->givenUser('new-user');
        $this->performPutUserConfirmationCode();
        $this->assertCreatedSuccess();
    }

    public function testPutUserBadConfirmationTokenNotFound()
    {
        $this->givenUser('new-user');
        $this->performPutUserConfirmationCode('wrong_one');
        $this->assertNotFoundError();
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

    private function performPutUserConfirmationCode($confirmationToken = null)
    {
        if ($confirmationToken === null) {
            $confirmationToken = $this->user->getConfirmationToken();
        }
        $this->response = $this->performClientRequest(
            'PUT',
            '/users/'.$this->user->getId().'/confirmation-token/'.$confirmationToken
        );
    }


    private function performPostUserForgotPassword(array $payload)
    {
        $this->performJsonClientRequest(
            'POST',
            '/users/forgot-password',
            $payload
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
    private function assertUserWithNameHasPhoneEquals($username, $phone)
    {
        $user = $this->findUserByUsername($username);
        $this->assertEquals(
            $phone,
            $user->getPhoneNumber(),
            'Phone should get normalized'
        );
    }

    private function assertPhoneEquals($phone)
    {
        $this->em->refresh($this->user);
        $this->assertEquals(
            $phone,
            $this->user->getPhoneNumber(),
            'Phone should get normalized'
        );
    }

    private function assertEmailEquals($email)
    {
        $this->em->refresh($this->user);
        $this->assertEquals(
            $email,
            $this->user->getEmail()
        );
    }


    private function givenUser($fixtureName)
    {
        $this->user = $this->fixtures->getReference($fixtureName);
        $this->userOriginalEmail = $this->user->getEmail();
        $this->userOriginalPhoneNumber = $this->user->getPhoneNumber();
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

    private function assertUserHasConfirmationTokenSet()
    {
        $this->em->refresh($this->user);
        $token = $this->user->getConfirmationToken();
        $this->assertFalse(
            empty($token),
            'confirmation token should not be empty'
        );
    }

    private function assertUserHasNoConfirmationTokenSet()
    {
        $this->em->refresh($this->user);
        $token = $this->user->getConfirmationToken();
        $this->assertTrue(
            empty($token),
            'confirmation token should be empty'
        );
    }
}
