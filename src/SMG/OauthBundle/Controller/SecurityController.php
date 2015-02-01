<?php

namespace SMG\OauthBundle\Controller;

use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends Controller
{
    /**
     * Check if a token is valid
     * HTTP status 200 => valid
     * HTTP status 410 (gone) => not valid
     *
     * @param Request $request     wrap http headers etc.
     * @param string  $accessToken access token to check the validity of
     *
     */
    public function accessTokenValidAction(
        Request $request,
        $accessToken
    ) {
        $response = new Response();

        try {
            $server = $this->get('fos_oauth_server.server');
            $accessToken = $server->verifyAccessToken(
                $accessToken,
                'user'
            );
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent("valid access token");

        } catch (OAuth2AuthenticateException $e) {
            $response->setStatusCode(Response::HTTP_GONE);
            $response->setContent('Invalid or expired token');
        }

        return $response;
    }
}

