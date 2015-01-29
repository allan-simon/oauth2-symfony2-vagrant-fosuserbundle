<?php

namespace SMG\OauthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityController extends Controller
{
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (
            null !== $session &&
            $session->has(SecurityContext::AUTHENTICATION_ERROR)
        ) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = null;
        }

        if ($error) {
             // WARNING! Symfony source code identifies this line as
             // a potential security threat.
            $error = $error->getMessage();
        }

        $lastUsername = (null === $session) ?
            '' :
            $session->get(SecurityContext::LAST_USERNAME)
        ;

        return $this->render(
            'SMGOauthBundle:Security:login.html.twig',
            array(
                'last_username' => $lastUsername,
                'error' => $error,
            )
        );
    }

    public function loginCheckAction(Request $request)
    {

    }
}

