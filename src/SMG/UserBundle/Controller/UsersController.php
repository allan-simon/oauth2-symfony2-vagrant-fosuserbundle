<?php

namespace SMG\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;
use Symfony\Component\Validator\Constraints as Assert;
use SMG\UserBundle\Entity\User;
use SMG\ManagerBundle\Controller\Traits\HandleUserTrait;

class UsersController extends FOSRestController
{
    use HandleUserTrait;

    const TOKEN_DIGITS = 6;
    /**
     * @Annotations\Post("/users")
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function postUsersAction(User $user)
    {
        $manager = $this->get('fos_user.user_manager');

        $token = $manager->deleteIfNonEnabledExists($user);
        $errors = $this->validates(
            $user,
            'mobile_app_registration'
        );
        if (count($errors) > 0) {
            return $this->handleView(
                new View($errors, Response::HTTP_BAD_REQUEST)
            );
        }

        // we need to create a new user to get the salt
        $newUser = $manager->createUser();

        $phoneNumber = $user->getPhoneNumber();
        $email = $user->getEmail();

        // normalize phone number with international suffix
        if (!is_null($phoneNumber)) {
            $phoneNumber = str_replace('+', '00', $phoneNumber);
        }

        $newUser->setUsername($user->getUsername());
        $newUser->setPhoneNumber($phoneNumber);
        $newUser->setEmail($email);
        $newUser->setPlainPassword($user->getPlainPassword());
        $newUser->setRoles(array('ROLE_USER'));

        if (is_null($token)) {
            $token = $this->generateToken();
        }
        $this->sendToken($email, $phoneNumber, $token);

        $newUser->setConfirmationToken($token);
        $newUser->setEnabled(false);
        $newUser->setLocked(true);
        $manager->updateUser($newUser);

        return $this->handleView(
            new View(
                array(
                    'id' => $newUser->getId(),
                ),
                Response::HTTP_ACCEPTED
            )
        );
    }

    /**
     * @Annotations\Patch("/users/{id}/password")
     */
    public function patchUserPasswordAction(User $user, Request $request)
    {
        $requestData = $this->requestIsJsonWithKeysOrThrow(
            $request,
            ['new_password', 'old_password']
        );

        if ($this->isPasswordCorrect($user, $requestData['old_password'])) {
            return $this->handleView(
                new View(
                    ['message' => 'bst.password.wrong'],
                    Response::HTTP_FORBIDDEN
                )
            );
        }

        $this->updateUserPassword($user, $requestData['new_password']);

        //TODO maybe replace by something more informative
        return $this->handleView(new View());
    }

    /**
     * Request change user's email or phone.
     *
     * @Annotations\Patch("/users/{id}/request-change-contact-info")
     */
    public function patchUserRequestChangeContactInfoAction(
        User $user,
        Request $request
    ) {
        $requestData = $this->requestIsJsonWithKeysOrThrow(
            $request,
            ['new_contact_info', 'password']
        );

        if ($this->isPasswordCorrect($user, $requestData['password'])) {
            return $this->handleView(
                new View(
                    ['message' => 'bst.password.wrong'],
                    Response::HTTP_FORBIDDEN
                )
            );
        }

        $contactInfo = $requestData['new_contact_info'];

        $validator = $this->container->get('validator');

        $token = $this->generateToken();
        $user->setConfirmationToken($token);

        $emailAssert = new Assert\Email();
        $emailAssert->message = 'bst.email.invalid';

        $errors = $validator->validateValue($contactInfo, $emailAssert);
        if (count($errors) === 0) {
            $this->sendTokenByEmail($contactInfo, $token);
            $this->get('fos_user.user_manager')->updateUser($user);

            return $this->handleView(new View());
        }

        $oldPhoneNumber = $user->getPhoneNumber();
        // we set user directly here so we can reuse the validator
        // of User entity for phone number
        $phoneNumber = str_replace('+', '00', $contactInfo);
        $user->setPhoneNumber($phoneNumber);

        $errors = $validator->validate($user, ['phone_check']);
        if (count($errors) === 0) {
            $this->sendTokenByPhone($phoneNumber, $token);

            // we put back the old phone number as it will be updated
            // only if the user send us back the confirmation token
            $user->setPhoneNumber($oldPhoneNumber);
            $this->get('fos_user.user_manager')->updateUser($user);

            return $this->handleView(new View());
        }

        return $this->handleView(
            new View(
                ['message' => 'bst.changecontactinfo.invalid'],
                Response::HTTP_BAD_REQUEST
            )
        );
    }

    /**
     * change user's email or phone, with validation code received in previous step.
     *
     * @Annotations\Patch("/users/{id}/contact-info")
     */
    public function patchUserChangeContactInfoAction(User $user, Request $request)
    {
        $requestData = $this->requestIsJsonWithKeysOrThrow(
            $request,
            ['new_contact_info', 'validation_code']
        );

        if ($requestData['validation_code'] !== $user->getConfirmationToken()) {
            throw new BadRequestHttpException('wrong validation code');
        }

        $contactInfo = $requestData['new_contact_info'];

        $manager = $this->get('fos_user.user_manager');
        $validator = $this->container->get('validator');

        $emailAssert = new Assert\Email();
        $emailAssert->message = 'bst.email.invalid';

        $errors = $validator->validateValue($contactInfo, $emailAssert);
        if (count($errors) === 0) {
            $this->get('logger')->info(
                'updated email of '.
                $user->getId().
                ' with '.
                $contactInfo
            );
            $user->setEmail($contactInfo);
            $manager->updateUser($user);

            return $this->handleView(new View());
        }

        // we set user directly here so we can reuse the validator
        // of User entity for phone number
        $phoneNumber = str_replace('+', '00', $contactInfo);
        $user->setPhoneNumber($phoneNumber);

        $errors = $validator->validate($user, ['phone_check']);
        if (count($errors) === 0) {
            $this->get('logger')->info(
                'updated phone of '.
                $user->getId().
                ' with '.
                $phoneNumber
            );
            $manager->updateUser($user);

            return $this->handleView(new View());
        }

        return $this->handleView(
            new View(
                ['message' => 'bst.changecontactinfo.invalid'],
                Response::HTTP_BAD_REQUEST
            )
        );
    }

    /**
     * Permit a user who has forgotten his password to request
     * a validation to be sent to either his email or phone number.
     *
     * @Annotations\Post("/users/forgot-password")
     */
    public function postUsersForgotPasswordAction(Request $request)
    {
        $requestData = $this->requestIsJsonWithKeysOrThrow(
            $request,
            ['contact_info']
        );

        $contactInfo = $requestData['contact_info'];

        $manager = $this->get('fos_user.user_manager');

        // it will check also email and phone number
        $user = $manager->loadUserByUsername($contactInfo);

        if (is_null($user)) {
            throw $this->createNotFoundException();
        }

        $token = $this->generateToken();
        $this->sendToken(
            $user->getEmail(),
            $user->getPhoneNumber(),
            $token
        );
        $user->setConfirmationToken($token);

        $manager->updateUser($user);

        return $this->handleView(
            new View(['id' => $user->getId()])
        );
    }

    /**
     * Used for a user to resend his confirmation token.
     *
     * @param User    $user    the user who's reseting password
     * @param Request $request
     *
     * @Annotations\Patch("/users/{id}/resend-confirmation-token")
     */
    public function patchUserResendConfirmationTokenAction(User $user)
    {
        $this->sendToken(
            $user->getEmail(),
            $user->getPhoneNumber(),
            $user->getConfirmationToken()
        );

        return $this->handleView(new View());
    }

    /**
     * Used for a user to reset his password if he's in possession
     * of a validation code send to him during an earlier step.
     *
     * @param User    $user    the user who's reseting password
     * @param Request $request
     *
     * @Annotations\Patch("/users/{id}/reset-password")
     */
    public function patchUserResetPasswordAction(
        User $user,
        Request $request
    ) {
        $requestData = $this->requestIsJsonWithKeysOrThrow(
            $request,
            ['new_password', 'validation_code']
        );

        if ($requestData['validation_code'] !== $user->getConfirmationToken()) {
            throw new BadRequestHttpException();
        }

        $this->updateUserPassword($user, $requestData['new_password']);

        return $this->handleView(new View());
    }

    /**
     * @Annotations\Put("/users/{id}/confirmation-token/{confirmationToken}")
     */
    public function putUserActivationCodeAction(User $user, $confirmationToken)
    {
        if ($user->getConfirmationToken() !== $confirmationToken) {
            throw $this->createNotFoundException('No confirmation token invalid');
        }

        $user->setEnabled(true);
        $user->setLocked(false);

        $this->get('fos_user.user_manager')->updateUser($user);

        return $this->handleView(
            new View(
                //TODO maybe replace by something more informative
                array(),
                Response::HTTP_CREATED
            )
        );
    }

    /**
     * @return bool
     */
    private function isPasswordCorrect(User $user, $password)
    {
        $encoderService = $this->get('security.encoder_factory');
        $encoder = $encoderService->getEncoder($user);
        $encodedPass = $encoder->encodePassword(
            $password,
            $user->getSalt()
        );

        return $encodedPass !== $user->getPassword();
    }

    /**
     */
    private function updateUserPassword(User $user, $newPassword)
    {
        $user->setPlainPassword($newPassword);
        $manager = $this->get('fos_user.user_manager');
        $manager->updateUser($user);
    }

    /**
     * Check if the JSON sent data is correct
     * for the current called action
     * and throws a bad request exception if the input is wrong.
     *
     * @return array
     */
    private function requestIsJsonWithKeysOrThrow(
        Request $request,
        array $keys,
        $message = 'bst.json.field_missing'
    ) {
        $json = json_decode($request->getContent(), true);

        foreach ($keys as $key) {
            if (empty($json[$key])) {
                throw new BadRequestHttpException($message);
            }
        }

        return $json;
    }

    /**
     * /!\ This method does not validate the input
     * making sure the email is a real email and phone is an actual phone
     * is the responsability of the calling method.
     *
     * @param string|null $email if null, email not sent
     * @param string|null $phone if null, SMS not sent
     * @param string      $token token to send to the user
     */
    private function sendToken(
        $email,
        $phone,
        $token
    ) {
        if (!empty($email)) {
            $this->sendTokenByEmail($email, $token);
        }

        if (!empty($phone)) {
            $this->sendTokenByPhone($phone, $token);
        }
    }

    /**
     *
     */
    private function sendTokenByEmail($email, $token)
    {
        $mailer = $this->get('mailer');
        $message = $mailer->createMessage()
            ->setSubject('Your token')
            ->setFrom($this->container->getParameter('mailer_user'))
            ->setTo($email)
            ->setBody('Token '.$token, 'text/plain');
        $mailer->send($message);
    }

    /**
     *
     */
    private function sendTokenByPhone($phone, $token)
    {
        $smsSender = $this->container->get('sms');
        $sms = $smsSender->createSms($phone, $token);
        $result = $smsSender->sendSms($sms);
        $this->get('logger')->info("sms token $token send to phone $phone");
        if (is_array($result)) {
            $this->get('logger')->info($result[0]);
        }
    }

    private function generateToken()
    {
        // shamelessy taken from http://stackoverflow.com/a/8216031/1185460
        // one could get('fos_user.util.token_generator') instead
        // but it's not mobile user friendly

        return str_pad(
            rand(0, pow(10, self::TOKEN_DIGITS) - 1),
            self::TOKEN_DIGITS,
            '0',
            STR_PAD_LEFT
        );
    }
}
