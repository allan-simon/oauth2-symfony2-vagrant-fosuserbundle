<?php

namespace SMG\OauthBundle\Entity;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    public $type;

    public function __construct()
    {
        parent::__construct();
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * Check if the given client type is the same as the current user.
     *
     * @param string $typeToCheck client type to check
     *
     * @return bool
     */
    public function isTypeEqualsTo($typeToCheck)
    {
        return $this->getType() === $typeToCheck;
    }
}
