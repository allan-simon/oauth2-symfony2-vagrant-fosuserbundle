<?php

namespace SMG\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;

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
            // Profile for validation of FOSUserBundle 
            // Default for own custom ones
            array('Profile', 'Default')
        );


        if (count($errors) > 0) {
            return $this->handleView(
                new View($errors, Response::HTTP_BAD_REQUEST)
            );
        }


        $manager = $this->get('fos_user.user_manager');
        // we need to create a new user to get the salt
        $newUser = $manager->createUser();

        $newUser->setUsername($user->getUsername());
        $newUser->setEmail($user->getEmail());
        $newUser->setPlainPassword($user->getPlainPassword());
        $newUser->setRoles(array('ROLE_USER'));
        $newUser->setEnabled(false);
        $newUser->setLocked(true);
        $newUser->setActivationCode("123456");
        $manager->updateUser($newUser);

        return $this->handleView(
            new View(
                array("id" => $newUser->getId()),
                Response::HTTP_ACCEPTED
            )
        );
    }


    /**
     * @Annotations\Put("/users/{id}/activation-code/{activationCode}")
     */
    public function putUserActivationCodeAction($id, $activationCode)
    {

        $manager = $this->get('fos_user.user_manager');
        $user = $manager->findUserBy(
            array(
                "id" => $id,
                "activationCode" => $activationCode,
            )
        );
        if (is_null($user)) {
            throw $this->createNotFoundException("No such user, or activation code invalid");
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
