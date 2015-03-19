<?php

namespace SMG\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
     * Change user password
     *
     * @Annotations\Patch("/users/{id}/password")
     */
    public function patchUserPasswordAction(User $user, Request $request)
    {
        $requestData = json_decode($request->getContent(), true);
        if (
            empty($requestData['new_password']) ||
            empty($requestData['old_password'])
        ) {
            return $this->handleView(
                new View(
                    ['message' => 'bst.changepassword.parameter_missing'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }
        $manager = $this->get('fos_user.user_manager');

        $encoderService = $this->get('security.encoder_factory');
        $encoder = $encoderService->getEncoder($user);
        $encodedPass = $encoder->encodePassword(
            $requestData['old_password'],
            $user->getSalt()
        );

        if ($encodedPass !== $user->getPassword()) {
            return $this->handleView(
                new View(
                    ['message' => 'bst.password.wrong'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        $user->setPlainPassword($requestData['new_password']);
        $manager->updateUser($user);

        //TODO maybe replace by something more informative
        return $this->handleView(new View());
    }

    /**
     * Change user's email or phone
     *
     * @Annotations\Patch("/users/{id}/contact-info")
     */
    public function patchUserContactInfodAction(User $user, Request $request)
    {
        $requestData = json_decode($request->getContent(), true);
        if (empty($requestData['new_contact_info'])) {
            return $this->handleView(
                new View(
                    ['message' => 'new_contact_info field missing'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }
        $contactInfo = $requestData['new_contact_info'];

        $manager = $this->get('fos_user.user_manager');
        $validator = $this->container->get('validator');

        $emailAssert = new Assert\Email();
        $emailAssert->message = 'bst.email.invalid';

        $errors = $validator->validateValue($contactInfo, $emailAssert);
        if (count($errors) === 0) {
            $user->setEmail($contactInfo);
            $user->setConfirmationToken("123456");
            //TODO send email
            $manager->updateUser($user);
            return $this->handleView(new View());
        }

        // we set user directly here so we can reuse the validator
        // of User entity for phone number
        $phoneNumber = str_replace('+', '00', $contactInfo);
        $user->setPhoneNumber($phoneNumber);

        $errors = $validator->validate($user, ['phone_check']);
        if (count($errors) === 0) {
            $user->setConfirmationToken("123456");
            //TODO send SMS
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
    
}
