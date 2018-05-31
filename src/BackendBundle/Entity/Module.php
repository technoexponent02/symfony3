<?php
// src/BackendBundle/Entity/Module.php
namespace BackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 * @ORM\Table(name="modules")
 * @ORM\Entity(repositoryClass="BackendBundle\Repository\ModuleRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="moduleName", message="Module name is already in use")
 */
class Module
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
    public $moduleName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $moduleUrlPath;

    /**
     * @ORM\Column(name="is_active", type="boolean", options={"comment":"1 = Active, 0 = Inactive","default" : 1})
     */
    public $isActive = 1;
    
    /**
     * @var datetime $created
     *
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
  	 * Many modules have Many UserCompany.
	 * @ORM\ManyToMany(targetEntity="UserCompany", mappedBy="modules")
     */
    public $userCompanies;

    /**
     * Many Modules have Many Company.
     * @ORM\ManyToMany(targetEntity="Company", mappedBy="modules")
     */
    public $companies;

    public function __construct()
    {
        $this->isActive = true;
        $this->userCompanies = new \Doctrine\Common\Collections\ArrayCollection();
        $this->companies = new \Doctrine\Common\Collections\ArrayCollection();
        // may not be needed, see section on salt below
        // $this->salt = md5(uniqid(null, true));
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
     * Set moduleName
     *
     * @param string $moduleName
     *
     * @return Module
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
    
        return $this;
    }

    /**
     * Get moduleName
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Module
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
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Module
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
     * @return Module
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
     * Set moduleUrlPath
     *
     * @param string $moduleUrlPath
     *
     * @return Module
     */
    public function setModuleUrlPath($moduleUrlPath)
    {
        $this->moduleUrlPath = $moduleUrlPath;
    
        return $this;
    }

    /**
     * Get moduleUrlPath
     *
     * @return string
     */
    public function getModuleUrlPath()
    {
        return $this->moduleUrlPath;
    }

    /**
     * Add userCompany
     *
     * @param \BackendBundle\Entity\UserCompany $userCompany
     *
     * @return Module
     */
    public function addUserCompany(\BackendBundle\Entity\UserCompany $userCompany)
    {
        $this->userCompanies[] = $userCompany;

        return $this;
    }

    /**
     * Remove userCompany
     *
     * @param \BackendBundle\Entity\UserCompany $userCompany
     */
    public function removeUserCompany(\BackendBundle\Entity\UserCompany $userCompany)
    {
        $this->userCompanies->removeElement($userCompany);
    }

    /**
     * Get userCompanies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserCompanies()
    {
        return $this->userCompanies;
    }

    /**
     * Add company
     *
     * @param \BackendBundle\Entity\Company $company
     *
     * @return Module
     */
    public function addCompany(\BackendBundle\Entity\Company $company)
    {
        $this->companies[] = $company;

        return $this;
    }

    /**
     * Remove company
     *
     * @param \BackendBundle\Entity\Company $company
     */
    public function removeCompany(\BackendBundle\Entity\Company $company)
    {
        $this->companies->removeElement($company);
    }

    /**
     * Get companies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCompanies()
    {
        return $this->companies;
    }
}
