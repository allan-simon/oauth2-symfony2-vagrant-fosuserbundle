<?php

namespace SMG\UserBundle\DataFixtures\ORM;

use SMG\OauthBundle\Entity\Client;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Adds one Video without- and one Video with Comments.
 */
class LoadClientData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $objectManager)
    {
        $c1 = new Client();
        $c1->setAllowedGrantTypes(
            ['password', 'refresh_token', 'token']
        );
        $c1->setType('backend');
        $this->addReference('new-client', $c1);

        $objectManager->persist($c1);
        $objectManager->flush();
    }

    /**
     * load fixtures in ascending order.
     */
    public function getOrder()
    {
        return 2;
    }
}
