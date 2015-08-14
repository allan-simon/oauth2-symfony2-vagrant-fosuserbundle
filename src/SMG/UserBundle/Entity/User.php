<?php

namespace SMG\UserBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * SMG\UserBundle\Entity\User.
 *
 * @ORM\Entity
 * @ORM\Table(name="oauth_users")
 * @ORM\AttributeOverrides({
 *     @ORM\AttributeOverride(
 *         name="email",
 *         column=@ORM\Column(
 *             type="string",
 *             nullable=true
 *         )
 *     ),
 *     @ORM\AttributeOverride(
 *          name="emailCanonical",
 *          column=@ORM\Column(
 *              type="string",
 *              name="email_canonical",
 *              length=255,
 *              nullable=true
 *          )
 *     ),
 * })
 */
class User extends BaseUser implements AdvancedUserInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $phoneNumber = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $lastName = null;

    public function __construct()
    {
        parent::__construct();
        $this->email = null;
        // note: when saving the user manager will put the email canonical
        // to empty string hence why we removed the unique constraint,
        // to avoid error being thrown when registering a user only with
        // phone number
        $this->emailCanonical = null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * TODO: problem on Symfony client side when use 
     * an already declared function name, need to
     * investigate why.
     *
     * @Serializer\VirtualProperty
     */
    public function getUserRoles()
    {
        return $this->roles;
    }

    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }
}
