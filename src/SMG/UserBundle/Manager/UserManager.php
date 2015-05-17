<?php

namespace SMG\UserBundle\Manager;

use FOS\UserBundle\Entity\UserManager as FOSUserManager;
use SMG\UserBundle\Entity\User;

class UserManager extends FOSUserManager
{
    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsernameOrEmail($username);

        if (is_null($user)) {
            // if not username or email, we now try phone number
            $phoneNumber = $this->normalizePhone($username);
            $user = $this->repository->findOneByPhoneNumber($phoneNumber);
        }

        if (is_null($user) || !$user->isEnabled()) {
            return;
        }

        return $user;
    }

    /**
     * If a User already exists with the same email or phone number but
     * that it was never enabled, we delete that previous user (this case
     * may happen if the user stop the registration process before sending
     * the validation code and then change phone etc.).
     * However to not confuse user, we keep the same confirmation token.
     *
     * @param User $user User to check the existence of
     *
     * @return string|null
     */
    public function deleteIfNonEnabledExists(User $user)
    {
        $email = $user->getEmail();
        $inDatabaseUser = null;
        if (!is_null($email)) {
            $inDatabaseUser = $this->findUserByUsernameOrEmail($email);
        }

        $phoneNumber = $user->getPhoneNumber();
        if (is_null($inDatabaseUser) && !is_null($phoneNumber)) {
            $phoneNumber = $this->normalizePhone($phoneNumber);
            $inDatabaseUser = $this->repository->findOneByPhoneNumber($phoneNumber);
        }

        $token = null;
        if (!is_null($inDatabaseUser) && !$inDatabaseUser->isEnabled()) {
            $token = $inDatabaseUser->getConfirmationToken();
            $this->deleteUser($inDatabaseUser);
        }

        return $token;
    }

    private function normalizePhone($phoneNumber)
    {
        $phoneNumber = str_replace('+', '00', $phoneNumber);

        return $phoneNumber;
    }
}
