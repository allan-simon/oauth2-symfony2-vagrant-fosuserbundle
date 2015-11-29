<?php

namespace SMG\ManagerBundle\Controller\Traits;

trait HandleUserTrait
{
    /**
     * Check if the information in $user is enough
     * and valid to create a new User in database.
     *
     * @param User   $user  user to validate
     * @param string $group validation group to apply
     *
     * @return array contains the error(s) list,
     *               empty if no error
     */
    private function validates($user, $group)
    {
        $validationRules = array($group);

        if (!is_null($user->getEmail())) {
            $validationRules[] = 'with_email';
        }

        if (!is_null($user->getPhoneNumber())) {
            $validationRules[] = 'with_phone';
        }

        $validator = $this->container->get('validator');
        $errors = $validator->validate(
            $user,
            $validationRules
        );

        return $errors;
    }
}
