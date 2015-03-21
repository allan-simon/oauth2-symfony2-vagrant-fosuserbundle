<?php

namespace SMG\UserBundle\DataFixtures\ORM;

use SMG\UserBundle\Entity\User;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Adds one Video without- and one Video with Comments
 */
class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $objectManager)
    {
        $u1 = new User();
        $u1->setUsername("allan");
        $u1->setEmail("allan@example.com");
        $u1->setPlainPassword("plop");
        $u1->setRoles(array('ROLE_USER'));
        $u1->setConfirmationToken("123456");
        $u1->setEnabled(false);
        $u1->setLocked(true);

        $this->addReference('new-user', $u1);

        $objectManager->persist($u1);
        $objectManager->flush();
    }

    /**
     * load fixtures in ascending order
     */
    public function getOrder()
    {
        return 1;
    }
}
