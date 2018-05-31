<?php
// src/BackendBundle/Entity/User.php
namespace BackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="BackendBundle\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="email", groups = {"Default", "Edit"}, message="Email is already in use")
 * @UniqueEntity(fields="username", groups = {"Default", "Edit"}, message="Username is already in use")
 */
class User implements UserInterface, \Serializable
{
    /**
     * @JMS\Expose
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @JMS\Expose
     * @Assert\NotBlank(groups = {"my_account"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $firstName;

    /**
     * @JMS\Expose
     * @Assert\NotBlank(groups = {"my_account"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $lastName;

    /**
     * @JMS\Expose
     * @Assert\Date(groups = {"my_account"})
     * @Assert\NotBlank(groups = {"my_account"})
     * @ORM\Column(type="date", length=255, nullable=true)
     */
    public $dob;

    /**
     * @JMS\Expose
     * @Assert\NotBlank(groups = {"Default", "Edit"})
     * @ORM\Column(type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @JMS\Expose
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = true,
     *     groups = {"Default", "Edit"}
     * )
     * @Assert\NotBlank(groups = {"Default", "Edit"})
     * @ORM\Column(type="string", length=255, unique=true)
     */
    public $email;

    /**
     * @Assert\NotBlank(groups = {"Default", "change_password"})
     * @Assert\Length(
     *      min = "5",
     *      max = "12",
     *      minMessage = "Password must be at least 5 characters long",
     *      maxMessage = "Password cannot be longer than than 12 characters",
     *      groups = {"Default", "change_password"}
     * )
     */
    public $plainPassword;

    /**
     * The below length depends on the "algorithm" you use for encoding
     * the password, but this works well with bcrypt.
     *
     * @ORM\Column(type="string", length=255)
     */
    public $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    public $apiAuthToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $forgetPasswordToken;

    /**
     * @JMS\Expose
     * @ORM\Column(name="is_active", type="boolean", options={"comment":"1 = Active, 0 = Inactive","default" : 1})
     */
    public $isActive = 1;
    
    /**
     * @Assert\NotBlank(groups = {"Default", "Edit"})
     * @ORM\Column(columnDefinition="TINYINT DEFAULT 1 NOT NULL COMMENT '1 = Admin, 2 = User'")
     */
    public $userType;

    /**
     * Admin has One switchUser.
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="switchUser_id", referencedColumnName="id", nullable = true)
     */
    private $switchUser;

    /**
     * @ORM\Column(type="integer", nullable = true)
     */
    public $loginCompany;

    /**
     * @var datetime $created
     *
     * @JMS\Expose
     * @ORM\Column(type="datetime")
     */
    public $created;

    /**
     * @var datetime $updated
     * 
     * @ORM\Column(type="datetime", nullable = true)
     */
    public $updated;

    /**
     * @JMS\Expose
     * @var ArrayCollection $usersCompanies
     *
     * @ORM\OneToMany(targetEntity="UserCompany", mappedBy="user")
     */
    private $usersCompanies;


    public function __construct()
    {
        $this->isActive = true;
        $this->usersCompanies = new \Doctrine\Common\Collections\ArrayCollection();
        // may not be needed, see section on salt below
        // $this->salt = md5(uniqid(null, true));
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function eraseCredentials()
    {
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set userType
     *
     * @param integer $userType
     *
     * @return User
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return integer
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Gets triggered only on insert

     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->created = new \DateTime("now");
    }

    /**
     * Gets triggered every time on update

     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updated = new \DateTime("now");
    }


    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return User
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set loginCompany
     *
     * @param integer $loginCompany
     *
     * @return User
     */
    public function setLoginCompany($loginCompany)
    {
        $this->loginCompany = $loginCompany;
    
        return $this;
    }

    /**
     * Get loginCompany
     *
     * @return integer
     */
    public function getLoginCompany()
    {
        return $this->loginCompany;
    }

    /**
     * Add usersCompany
     *
     * @param \BackendBundle\Entity\UserCompany $usersCompany
     *
     * @return User
     */
    public function addUsersCompany(\BackendBundle\Entity\UserCompany $usersCompany)
    {
        $this->usersCompanies[] = $usersCompany;

        return $this;
    }

    /**
     * Remove usersCompany
     *
     * @param \BackendBundle\Entity\UserCompany $usersCompany
     */
    public function removeUsersCompany(\BackendBundle\Entity\UserCompany $usersCompany)
    {
        $this->usersCompanies->removeElement($usersCompany);
    }

    /**
     * Get usersCompanies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsersCompanies($show_all = false, $is_active = 1)
    {
        $criteria = Criteria::create();
        if ($show_all !== true && $is_active == 1) 
        {
            $criteria->where(Criteria::expr()->eq('isActive', 1));
        }
        elseif($show_all !== true && $is_active == 0)
        {
            $criteria->where(Criteria::expr()->eq('isActive', 0));
        }
        return $this->usersCompanies->matching($criteria);
    }

    /**
     * Set switchUser
     *
     * @param \BackendBundle\Entity\User $switchUser
     *
     * @return User
     */
    public function setSwitchUser(\BackendBundle\Entity\User $switchUser = null)
    {
        $this->switchUser = $switchUser;

        return $this;
    }

    /**
     * Get switchUser
     *
     * @return \BackendBundle\Entity\User
     */
    public function getSwitchUser()
    {
        return $this->switchUser;
    }

    /**
     * Set forgetPasswordToken
     *
     * @param string $forgetPasswordToken
     *
     * @return User
     */
    public function setForgetPasswordToken($forgetPasswordToken)
    {
        $this->forgetPasswordToken = $forgetPasswordToken;

        return $this;
    }

    /**
     * Get forgetPasswordToken
     *
     * @return string
     */
    public function getForgetPasswordToken()
    {
        return $this->forgetPasswordToken;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }


    /**
     * Set dob
     *
     * @param \DateTime $dob
     *
     * @return User
     */
    public function setDob($dob)
    {
        $this->dob = $dob;

        return $this;
    }

    /**
     * Get dob
     *
     * @return \DateTime
     */
    public function getDob()
    {
        return $this->dob;
    }

    /**
     * Set apiAuthToken
     *
     * @param string $apiAuthToken
     *
     * @return User
     */
    public function setApiAuthToken($apiAuthToken)
    {
        $this->apiAuthToken = $apiAuthToken;

        return $this;
    }

    /**
     * Get apiAuthToken
     *
     * @return string
     */
    public function getApiAuthToken()
    {
        return $this->apiAuthToken;
    }
}
