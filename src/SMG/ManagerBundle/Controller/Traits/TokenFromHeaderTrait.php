<?php

namespace SMG\ManagerBundle\Controller\Traits;

trait TokenFromHeaderTrait
{
    /**
     * Extract the access token from the HTTP request header and
     * format it from oauth2 format to return only the token string.
     *
     * @return string access token
     */
    public function getAccessTokenString()
    {
        $request = $this->getRequest();
        $headers = $request->headers->all();

        return str_replace(
            'Bearer ',
            '',
            $headers['authorization']
        );
    }

    /**
     * Check if the user is currently through a token
     * linked to allowed client(s) for an action.
     *
     * @param string $allowedType client type to check
     *
     * @return bool
     *
     * @throws AccessDeniedException
     */
    public function throwIfClientNot($allowedType)
    {
        $accessToken = $this->getFOSOauthServer()->verifyAccessToken(
            $this->getAccessTokenString(),
            'user'
        );

        if (!$accessToken->getClient()->isTypeEqualsTo($allowedType)) {
            throw $this->createAccessDeniedException(
                'This user type is not allowed for this operation.'
            );
        }
    }

    /**
     * get current by accessToken.
     *
     * @return User
     */
    public function getCurrentUser()
    {
        $accessToken = $this->getFOSOauthServer()->verifyAccessToken(
            $this->getAccessTokenString(),
            'user'
        );

        return $accessToken->getUser();
    }

    /**
     *
     */
    private function getFOSOauthServer()
    {
        return $this->get('fos_oauth_server.server');
    }
}
