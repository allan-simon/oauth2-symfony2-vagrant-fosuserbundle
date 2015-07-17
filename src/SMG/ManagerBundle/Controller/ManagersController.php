<?php

namespace SMG\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations;
use SMG\UserBundle\Entity\User;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class ManagersController extends FOSRestController
{
    use Traits\TokenFromHeaderTrait;
    use Traits\HandleUserTrait;

    /**
     * NOTE: use annotation for routing here even
     * if the FOSRestBundle is automatically able
     * to handle them. In fact, ParamConverter is 
     * not supported by FOSRestController.
     *
     * @Annotations\Post("/admin/users")
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function postUserAction(User $user)
    {
        $this->throwIfClientNot('backend');

        // TODO: 13 - refactor common parameters
        // settings in the HandleUserTrait.
        $manager = $this->get('fos_user.user_manager');
        $manager->deleteIfNonEnabledExists($user);

        $errors = $this->validates($user);
        if (count($errors) > 0) {
            return $this->handleView(
                new View($errors, Response::HTTP_BAD_REQUEST)
            );
        }

        $newUser = $manager->createUser();

        $phoneNumber = $user->getPhoneNumber();
        $email = $user->getEmail();

        if (!is_null($phoneNumber)) {
            $phoneNumber = str_replace('+', '00', $phoneNumber);
        }

        $newUser->setPhoneNumber($phoneNumber);
        $newUser->setEmail($email);
        $newUser->setUsername($user->getUsername());
        $newUser->setPlainPassword($user->getPlainPassword());
        $newUser->setRoles($user->getRoles());
        $newUser->setEnabled(true);
        $newUser->setLocked(false);
        $manager->updateUser($newUser);

        return $this->handleView(
            new View(
                array(
                    'id' => $newUser->getId()
                ),
                Response::HTTP_CREATED
            )
        );
    }
}
