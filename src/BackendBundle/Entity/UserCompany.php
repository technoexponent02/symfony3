<?php
// src/BackendBundle/Entity/UserCompany.php
namespace BackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 * @ORM\Table(name="users_companies")
 * @UniqueEntity(
 *     fields={"user", "company"},
 *     errorPath="company",
 *     message="This user is already in use on that company."
 * )
 * @ORM\Entity(repositoryClass="BackendBundle\Repository\UserCompanyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserCompany
{
    /**
     * @JMS\Expose
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="usersCompanies")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    public $user;

    /**
     * @JMS\Expose
     * @var Company $company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="usersCompanies")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    public $company;

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
     * @JMS\Expose
     * Many UserCompany have Many modules.
     * @ORM\ManyToMany(targetEntity="Module", inversedBy="userCompanies")
     * @ORM\JoinTable(name="user_companies_modules")
     */
    public $modules;

    public function __construct()
    {
        $this->modules = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set created
     *
     * @param \DateTime $created
     *
     * @return UserRoleCompany
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
     * @return UserRoleCompany
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
     * Set user
     *
     * @param \BackendBundle\Entity\User $user
     *
     * @return UserCompany
     */
    public function setUser(\BackendBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \BackendBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set company
     *
     * @param \BackendBundle\Entity\Company $company
     *
     * @return UserCompany
     */
    public function setCompany(\BackendBundle\Entity\Company $company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return \BackendBundle\Entity\Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Add module
     *
     * @param \BackendBundle\Entity\Module $module
     *
     * @return UserCompany
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
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return UserCompany
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
}
