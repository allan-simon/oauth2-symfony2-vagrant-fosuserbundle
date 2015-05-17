<?php

namespace SMG\UserBundle\Manager;

use FOS\UserBundle\Entity\UserManager as FOSUserManager;

class UserManager extends FOSUserManager
{
    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsernameOrEmail($username);

        if (is_null($user)) {
            // if not username or email, we now try phone number
            $phoneNumber = str_replace('+', '00', $username);
            $user = $this->repository->findOneByPhoneNumber($phoneNumber);
        }

        if (is_null($user) || !$user->isEnabled()) {
            return;
        }

        return $user;
    }
}
