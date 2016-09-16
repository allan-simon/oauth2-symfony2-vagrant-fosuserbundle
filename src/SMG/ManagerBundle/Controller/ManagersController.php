<?php

namespace SMG\ManagerBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use SMG\UserBundle\Entity\User;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @Annotations\Post("/users")
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function postUserAction(User $user)
    {
        $this->throwIfClientNot('backend');

        // TODO: 13 - refactor common parameters
        // settings in the HandleUserTrait.
        $manager = $this->get('fos_user.user_manager');
        $manager->deleteIfNonEnabledExists($user);

        //TODO: 16 - use the mobile_app_registration
        //group for now, but should be renamed
        //for the backend
        $errors = $this->validates(
            $user,
            'mobile_app_registration'
        );
        if (count($errors) > 0) {
            return $this->handleView(
                new View($errors, Response::HTTP_BAD_REQUEST)
            );
        }

        $newUser = $manager->createUser();

        $phoneNumber = $user->getPhoneNumber();
        if (!is_null($phoneNumber)) {
            $phoneNumber = str_replace('+', '00', $phoneNumber);
        }

        $newUser->setPhoneNumber($phoneNumber);
        $newUser->setEmail($user->getEmail());
        $newUser->setUsername($user->getUsername());
        $newUser->setPlainPassword($user->getPlainPassword());
        $newUser->setRoles($user->getRoles());
        $newUser->setEnabled(true);
        $newUser->setLocked(false);
        $manager->updateUser($newUser);

        return $this->handleView(
            new View(
                array(
                    'id' => $newUser->getId(),
                ),
                Response::HTTP_CREATED
            )
        );
    }

    /**
     * @Annotations\Put("/users/{id}")
     *
     * @ParamConverter("updatedUser", converter="fos_rest.request_body")
     *
     * @param User $user
     * @param User $updatedUser
     */
    public function putUserAction(
        User $user,
        User $updatedUser
    ) {
        $this->throwIfClientNot('backend');

        $errors = $this->validates(
            $updatedUser,
            'backend_user_edit'
        );
        if (count($errors) > 0) {
            return $this->handleView(
                new View($errors, Response::HTTP_BAD_REQUEST)
            );
        }

        $user->setUsername($updatedUser->getUsername());
        $user->setEmail($updatedUser->getEmail());
        $user->setPhoneNumber($updatedUser->getPhoneNumber());
        $user->setRoles($updatedUser->getRoles());

        $this->get('fos_user.user_manager')->updateUser($user);

        return $this->handleView(
            new View(
                array(
                    'id' => $user->getId(),
                ),
                Response::HTTP_OK
            )
        );
    }

    /**
     * @param User    $user
     * @param Request $request
     */
    public function putUserRolesAction(
        User $user,
        Request $request
    ) {
        $this->throwIfClientNot('backend');

        $roles = json_decode($request->getContent(), true);

        $user->setRoles($roles);

        $this->get('fos_user.user_manager')->updateUser($user);
    }

    /**
     * @param User $user
     */
    public function getUserAction(User $user)
    {
        $this->throwIfClientNot('backend');

        return $user;
    }

    /**
     * Disable one given user.
     *
     * @param User $user
     */
    public function patchUserDisableAction(User $user)
    {
        $this->throwIfClientNot('backend');

        $user->setEnabled(false);

        $this->get('fos_user.user_manager')->updateUser($user);
    }

    /**
     * @param User $user
     */
    public function patchUserEnableAction(User $user)
    {
        $this->throwIfClientNot('backend');

        $user->setEnabled(true);

        $this->get('fos_user.user_manager')->updateUser($user);
    }

    /**
     * @param User    $user
     * @param Request $request
     *
     * @Annotations\put("/users/{id}/password")
     *
     * @return Response
     */
    public function putUsersPasswordAction(User $user, Request $request)
    {
        $this->throwIfClientNot('backend');

        if (!$this->isCurrentUserAdmin()) {
            return $this->handleView(
                new View(
                    ['message' => 'bst.admin.only'],
                    Response::HTTP_FORBIDDEN
                )
            );
        }

        $requestData = $this->requestIsJsonWithKeysOrThrow(
            $request,
            ['new_password']
        );

        $user->setPlainPassword($requestData['new_password']);

        $this->get('fos_user.user_manager')->updateUser($user);

        return $this->handleView(
            new View(
                null,
                Response::HTTP_NO_CONTENT
            )
        );
    }

    /**
     * Check if the JSON sent data is correct
     * for the current called action
     * and throws a bad request exception if the input is wrong.
     *
     * @param Request $request
     * @param array   $keys
     * @param string  $message
     *
     * @return array
     *
     * @throws BadRequestHttpException
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

    private function isCurrentUserAdmin()
    {
        return in_array(
            'ROLE_ADMINPANEL',
            $this->getCurrentUser()->getRoles()
        );
    }
}
