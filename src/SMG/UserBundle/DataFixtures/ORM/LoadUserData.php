<?php

namespace SMG\UserBundle\DataFixtures\ORM;

use SMG\UserBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Adds one Video without- and one Video with Comments.
 */
class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $objectManager)
    {
        $u1 = new User();
        $u1->setUsername('allan');
        $u1->setEmail('allan@example.com');
        $u1->setPlainPassword('plop');
        $u1->setRoles(array('ROLE_USER'));
        $u1->setConfirmationToken('123456');
        $u1->setEnabled(false);
        $u1->setLocked(true);

        $this->addReference('new-user', $u1);

        $u2 = new User();
        $u2->setUsername('Raphael');
        $u2->setEmail('raphael@example.com');
        $u2->setPlainPassword('plop');
        $u2->setRoles(array('ROLE_USER'));
        $u2->setPhoneNumber('123456789');
        $u2->setConfirmationToken('');
        $u2->setEnabled(true);
        $u2->setLocked(false);

        $this->addReference('user-without-confirmation-token', $u2);

        $u3 = new User();
        $u3->setUsername('Bobthesponge');
        $u3->setEmail('bobthesponge@example.com');
        $u3->setPlainPassword('plop');
        $u3->setRoles(array('ROLE_USER'));
        $u3->setConfirmationToken('123456');
        $u3->setEnabled(true);
        $u3->setLocked(false);

        $this->addReference('user-with-confirmation-token', $u3);

        $u4 = new User();
        $u4->setUsername('admin');
        $u4->setEmail('admin@example.com');
        $u4->setPlainPassword('admin');
        $u4->setRoles(['ROLE_ADMINPANEL']);
        $u4->setConfirmationToken('123456');
        $u4->setEnabled(true);
        $u4->setLocked(false);

        $this->addReference('admin', $u4);

        $objectManager->persist($u1);
        $objectManager->persist($u2);
        $objectManager->persist($u3);
        $objectManager->persist($u4);
        $objectManager->flush();
    }

    /**
     * load fixtures in ascending order.
     */
    public function getOrder()
    {
        return 1;
    }
}
