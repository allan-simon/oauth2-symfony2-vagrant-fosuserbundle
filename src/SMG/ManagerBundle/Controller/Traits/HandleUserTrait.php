<?php

namespace SMG\ManagerBundle\Controller\Traits;

trait HandleUserTrait
{
    /**
     * Check if the information in $user is enough
     * and valid to create a new User in database.
     *
     * @param User $currentUser current user in DB
     * @param User $updatedUser user to validate
     * @param string $group validation group to apply
     *
     * @return array contains the error(s) list,
     * empty if no error
     */
    private function validates($updatedUser, $group, $currentUser = null)
    {
        $validationRules = array($group);

        if (
            !is_null($updatedUser->getEmail()) &&
            !$this->haveSameEmail($currentUser, $updatedUser)
        ) {
            $validationRules[] = 'with_email';
        }

        if (
            !is_null($updatedUser->getPhoneNumber()) &&
            !$this->haveSamePhoneNumber($currentUser, $updatedUser)
        ) {
            $validationRules[] = 'with_phone';
        }

        if (
            !is_null($updatedUser->getUsername()) &&
            !$this->haveSameUsername($currentUser, $updatedUser)
        ) {
            $validationRules[] = 'with_username';
        }

        $validator = $this->container->get('validator');
        $errors = $validator->validate(
            $updatedUser,
            $validationRules
        );

        return $errors;
    }

    /**
     *
     */
    private function haveSameUsername($currentUser, $updatedUser)
    {
        return is_null($currentUser) ?
            false :
            $currentUser->getUsername() === $updatedUser->getUsername()
        ;
    }

    /**
     *
     */
    private function haveSameEmail($currentUser, $updatedUser)
    {
        return is_null($currentUser) ?
            false :
            $currentUser->getEmail() === $updatedUser->getEmail()
        ;
    }

    /**
     *
     */
    private function haveSamePhoneNumber($currentUser, $updatedUser)
    {
        return is_null($currentUser) ?
            false :
            $currentUser->getPhoneNumber() === $updatedUser->getPhoneNumber()
        ;
    }
}
