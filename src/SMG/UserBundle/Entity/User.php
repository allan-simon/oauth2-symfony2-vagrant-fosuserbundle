<?php

namespace SMG\UserBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
* SMG\UserBundle\Entity\User
*
* @ORM\Entity
* @ORM\Table(name="oauth_users")
*/
class User extends BaseUser
{
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $activationCode = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getActivationCode()
    {
        return $activationCode;
    }

    public function setActivationCode($activationCode)
    {
        $this->activationCode = $activationCode;

        return $this;
    }
}
