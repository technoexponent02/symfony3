<?php
// src/BackendBundle/Entity/Company.php
namespace BackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 * @ORM\Table(name="companies")
 * @ORM\Entity(repositoryClass="BackendBundle\Repository\CompanyRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="connectionName", message="Connection name is already in use")
 * @UniqueEntity(fields="companyDbName", message="Company DB name is already in use")
 */
class Company
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
     * @ORM\Column(type="string", length=255, unique=true)
     */
    public $connectionName;

    /**
     * @JMS\Expose
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    public $companyName;

    /**
     * @ORM\Column(type="string", length=255, options={"default" : "127.0.0.1"})
     */
    public $companyDbHost = '127.0.0.1';

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, unique=true)
     */
    public $companyDbName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $companyDbUser;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $companyDbPassword;

    /**
     * @JMS\Expose
     * @ORM\Column(name="is_active", type="boolean", options={"comment":"1 = Active, 0 = Inactive","default" : 1})
     */
    public $isActive = 1;

    /**
     * @ORM\Column(name="location_type", columnDefinition="TINYINT DEFAULT 1 NOT NULL COMMENT '1 = Local, 2 = External'")
     */
    public $locationType = 1;

    /**
     * @ORM\Column(name="db_type", columnDefinition="TINYINT DEFAULT 1 NOT NULL COMMENT '1 = New, 2 = Existing'")
     */
    public $dbType = 1;
    
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
     * @var ArrayCollection $usersCompanies
     *
     * @ORM\OneToMany(targetEntity="UserCompany", mappedBy="company")
     */
    public $usersCompanies;

    /**
     * Many Company have Many modules.
     * @ORM\ManyToMany(targetEntity="Module", inversedBy="companies")
     * @ORM\JoinTable(name="companies_modules")
     */
    public $modules;

    public function __construct()
    {
        $this->isActive = true;
        $this->usersCompanies = new \Doctrine\Common\Collections\ArrayCollection();
        $this->modules = new \Doctrine\Common\Collections\ArrayCollection();
        // may not be needed, see section on salt below
        // $this->salt = md5(uniqid(null, true));
    }

    public function getCompanyName()
    {
        return $this->companyName;
    }

    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }

    public function setConnectionName($connectionName)
    {
        $this->connectionName = $connectionName;
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
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Company
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
     * @return Company
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
     * @return Company
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
     * Set companyDbName
     *
     * @param string $companyDbName
     *
     * @return Company
     */
    public function setCompanyDbName($companyDbName)
    {
        $this->companyDbName = $companyDbName;
    
        return $this;
    }

    /**
     * Get companyDbName
     *
     * @return string
     */
    public function getCompanyDbName()
    {
        return $this->companyDbName;
    }

    /**
     * Set companyDbUser
     *
     * @param string $companyDbUser
     *
     * @return Company
     */
    public function setCompanyDbUser($companyDbUser)
    {
        $this->companyDbUser = $companyDbUser;
    
        return $this;
    }

    /**
     * Get companyDbUser
     *
     * @return string
     */
    public function getCompanyDbUser()
    {
        return $this->companyDbUser;
    }

    /**
     * Set companyDbPassword
     *
     * @param string $companyDbPassword
     *
     * @return Company
     */
    public function setCompanyDbPassword($companyDbPassword)
    {
        $this->companyDbPassword = $companyDbPassword;
    
        return $this;
    }

    /**
     * Get companyDbPassword
     *
     * @return string
     */
    public function getCompanyDbPassword()
    {
        return $this->companyDbPassword;
    }


    /**
     * Add usersCompany
     *
     * @param \BackendBundle\Entity\UserCompany $usersCompany
     *
     * @return Company
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
     * Set companyDbHost
     *
     * @param string $companyDbHost
     *
     * @return Company
     */
    public function setCompanyDbHost($companyDbHost)
    {
        $this->companyDbHost = $companyDbHost;

        return $this;
    }

    /**
     * Get companyDbHost
     *
     * @return string
     */
    public function getCompanyDbHost()
    {
        return $this->companyDbHost;
    }

    /**
     * Add module
     *
     * @param \BackendBundle\Entity\Module $module
     *
     * @return Company
     */
    public function addModule(\BackendBundle\Entity\Module $module)
    {
        $this->modules[] = $module;

        return $this;
    }

    /**
     * Remove module
     *
     * @param \BackendBundle\Entity\Module $module
     */
    public function removeModule(\BackendBundle\Entity\Module $module)
    {
        $this->modules->removeElement($module);
    }

    /**
     * Get modules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Set dbType
     *
     * @param string $dbType
     *
     * @return Company
     */
    public function setDbType($dbType)
    {
        $this->dbType = $dbType;

        return $this;
    }

    /**
     * Get dbType
     *
     * @return string
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * Set locationType
     *
     * @param string $locationType
     *
     * @return Company
     */
    public function setLocationType($locationType)
    {
        $this->locationType = $locationType;

        return $this;
    }

    /**
     * Get locationType
     *
     * @return string
     */
    public function getLocationType()
    {
        return $this->locationType;
    }
}
