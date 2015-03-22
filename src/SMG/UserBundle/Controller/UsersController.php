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

class UsersController extends FOSRestController
{
    /**
     * @Annotations\Post("/users")
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function postUsersAction(User $user)
    {
        $validator = $this->container->get('validator');
        $errors = $validator->validate(
            $user,
            array('mobile_app_registration')
        );


        if (count($errors) > 0) {
            return $this->handleView(
                new View($errors, Response::HTTP_BAD_REQUEST)
            );
        }

        $manager = $this->get('fos_user.user_manager');
        $generator = $this->get('fos_user.util.token_generator');
        // we need to create a new user to get the salt
        $newUser = $manager->createUser();

        $phoneNumber = $user->getPhoneNumber();

        // normalize phone number with international suffix
        if (!is_null($phoneNumber)) {
            $phoneNumber = str_replace('+', '00', $phoneNumber);
        }

        //TODO find a better way to have nullable email
        //     i.e for the moment we're setting it to empty string
        $email = (string) $user->getEmail();

        $newUser->setUsername($user->getUsername());
        $newUser->setPhoneNumber($phoneNumber);
        $newUser->setEmail($email);
        $newUser->setPlainPassword($user->getPlainPassword());
        $newUser->setRoles(array('ROLE_USER'));
// TODO: #1 when we will have implemented the email sending
//      $newUser->setConfirmationToken($generator->generateToken());
        $newUser->setConfirmationToken("123456");
        $newUser->setEnabled(false);
        $newUser->setLocked(true);
        $manager->updateUser($newUser);

        return $this->handleView(
            new View(
                array(
                    "id" => $newUser->getId(),
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
     * Request change user's email or phone
     *
     * @Annotations\Patch("/users/{id}/request-change-contact-info")
     */
    public function patchUserRequestChangeContactInfoAction(
        User $user,
        Request $request
    ) {
        $requestData = $this->requestIsJsonWithKeysOrThrow(
            $request,
            ['new_contact_info']
        );

        $contactInfo = $requestData['new_contact_info'];

        $manager = $this->get('fos_user.user_manager');
        $validator = $this->container->get('validator');

        $emailAssert = new Assert\Email();
        $emailAssert->message = 'bst.email.invalid';

        $errors = $validator->validateValue($contactInfo, $emailAssert);
        if (count($errors) === 0) {
            $user->setConfirmationToken("123456");

            //TODO send email
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
            $user->setConfirmationToken("123456");
            $user->setPhoneNumber($oldPhoneNumber);
            //TODO send SMS
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
     * change user's email or phone, with validation code received in previous step
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
            throw new BadRequestHttpException();
        }

        $contactInfo = $requestData['new_contact_info'];

        $manager = $this->get('fos_user.user_manager');
        $validator = $this->container->get('validator');

        $emailAssert = new Assert\Email();
        $emailAssert->message = 'bst.email.invalid';

        $errors = $validator->validateValue($contactInfo, $emailAssert);
        if (count($errors) === 0) {
            $user->setEmail($contactInfo);
            $manager->updateUser($user);
            return $this->handleView(new View());
        }

        $oldPhoneNumber = $user->getPhoneNumber();
        // we set user directly here so we can reuse the validator
        // of User entity for phone number
        $phoneNumber = str_replace('+', '00', $contactInfo);
        $user->setPhoneNumber($phoneNumber);

        $errors = $validator->validate($user, ['phone_check']);
        if (count($errors) === 0) {
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
     * a validation to be sent to either his email or phone number
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

        $userByEmail = $this->findUserByEmail($contactInfo);
        $userByPhone = $this->findUserByPhoneNumber($contactInfo);
        $user = (
            $userByEmail !== null ?
            $userByEmail :
            $userByPhone
        );

        if ($userByEmail === null && $userByPhone === null) {
            throw $this->createNotFoundException();
        }

        $user->setConfirmationToken("123456");
        //TODO send email or sms

        $this->get('fos_user.user_manager')->updateUser($user);
        return $this->handleView(
            new View(['id' => $user->getId()])
        );
    }

    /**
     * Used for a user to reset his password if he's in possession
     * of a validation code send to him during an earlier step
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
    public function putUserActivationCodeAction($id, $confirmationToken)
    {

        $manager = $this->get('fos_user.user_manager');
        $user = $manager->findUserBy(
            array(
                "id" => $id,
                "confirmationToken" => $confirmationToken,
            )
        );
        if (is_null($user)) {
            throw $this->createNotFoundException("No such user, or confirmation token invalid");
        }

        $user->setEnabled(true);
        $user->setLocked(false);
        $manager->updateUser($user);

        return $this->handleView(
            new View(
                //TODO maybe replace by something more informative
                array(),
                Response::HTTP_CREATED
            )
        );

    } 

    /**
     * TODO: move in User manager
     */
    private function findUserByEmail($email)
    {
        return $this->getDoctrine()
            ->getRepository('SMGUserBundle:User')
            ->findOneByEmail($email);
    }

    /**
     * TODO: move in User manager
     */
    private function findUserByPhoneNumber($phoneNumber)
    {
        return $this->getDoctrine()
            ->getRepository('SMGUserBundle:User')
            ->findOneByPhoneNumber($phoneNumber);
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
     * @return null
     */
    private function updateUserPassword(User $user, $newPassword)
    {
        $user->setPlainPassword($newPassword);
        $manager = $this->get('fos_user.user_manager');
        $manager->updateUser($user);
    }
    
    /**
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
                throw new BadRequestHttpException($key.' is missing');
            }
        }
        return $json;
    }
}
