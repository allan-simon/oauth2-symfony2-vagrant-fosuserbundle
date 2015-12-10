<?php

namespace SMG\OauthBundle\Controller;

use OAuth2\OAuth2AuthenticateException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends Controller
{
    /**
     * Check if a token is valid
     * HTTP status 200 => valid
     * HTTP status 410 (gone) => not valid.
     *
     * @param string $accessTokenString access token to check the validity of
     *
     * @return Response
     */
    public function accessTokenValidAction($accessTokenString)
    {
        try {
            $server = $this->get('fos_oauth_server.server');
            $accessToken = $server->verifyAccessToken(
                $accessTokenString,
                'user'
            );
            $toSerialize = [];

            $user = $accessToken->getUser();
            if (!is_null($user)) {
                $toSerialize = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'phone_number' => $user->getPhoneNumber(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),

                ];
            }

            $client = $accessToken->getClient();

            if (!is_null($client)) {
                $toSerialize['client'] = [
                    'id' => $client->getId(),
                    'type' => $client->getType(),
                    'meta' => $client->getMeta(),
                ];
            }

            //TODO: replace by a serializer maybe?
            return new JsonResponse($toSerialize);
        } catch (OAuth2AuthenticateException $e) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_GONE);
            $response->setContent('Invalid or expired token');

            return $response;
        }
    }
}
