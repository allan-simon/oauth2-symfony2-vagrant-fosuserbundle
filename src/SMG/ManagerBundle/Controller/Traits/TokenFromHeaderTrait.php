<?php

namespace SMG\ManagerBundle\Controller\Traits;

trait TokenFromHeaderTrait
{
    /**
     * Extract the access token from the HTTP request header and
     * format it from oauth2 format to return only the token string.
     *
     * @return string|null access token
     */
    public function getAccessTokenString()
    {
        $request = $this->getRequest();
        $headers = $request->headers->all();

        if (
            !isset($headers['authorization']) ||
            empty($headers['authorization'][0])
        ) {
            return;
        }

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
        $accessTokenStr = $this->getAccessTokenString();
        if (is_null($accessTokenStr)) {
            throw $this->createAccessDeniedException(
                'Access token missing'
            );
        }

        $accessToken = $this->getFOSOauthServer()->verifyAccessToken(
            $accessTokenStr,
            'user'
        );

        if (!$accessToken->getClient()->isTypeEqualsTo($allowedType)) {
            throw $this->createAccessDeniedException(
                'This client type is not allowed for this operation.'
            );
        }
    }

    /**
     *
     */
    private function getFOSOauthServer()
    {
        return $this->get('fos_oauth_server.server');
    }
}
