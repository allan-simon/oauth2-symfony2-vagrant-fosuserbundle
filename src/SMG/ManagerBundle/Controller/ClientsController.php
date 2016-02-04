<?php

namespace SMG\ManagerBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use SMG\OauthBundle\Entity\Client;

class ClientsController extends FOSRestController
{
    use Traits\TokenFromHeaderTrait;

    /**
     * List all oauth clients created.
     */
    public function getClientsAction()
    {
        $this->throwIfClientNot('backend');
        $clientManager = $this->get(
            'fos_oauth_server.client_manager.default'
        );

        $class = $clientManager->getClass();

        return $this->getDoctrine()->getRepository($class)->findAll();
    }

    /**
     * Create a new oauth client.
     *
     * @param Client $client Client posted by the caller
     *
     * @Annotations\Post("/clients")
     *
     * @ParamConverter(
     *     "client",
     *     converter="fos_rest.request_body"
     * )
     *
     * @return Client
     */
    public function postClientAction(Client $client)
    {
        $this->throwIfClientNot('backend');

        $clientManager = $this->get(
            'fos_oauth_server.client_manager.default'
        );
        $newClient = $clientManager->createClient();

        $newClient->setType($client->getType());
        $newClient->setMeta($client->getMeta());
        if ($client->getAllowedGrantTypes() !== null) {
            $newClient->setAllowedGrantTypes($client->getAllowedGrantTypes());
        }

        $clientManager->updateClient($newClient);

        return $newClient;
    }
}
