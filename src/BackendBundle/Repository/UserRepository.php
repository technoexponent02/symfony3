<?php

namespace BackendBundle\Repository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
	public function getSubaccounts($auth_user, $company)
	{
	    $qb = $this->createQueryBuilder('u');
	    $qb->select('u')
	    	->innerJoin('u.usersCompanies','urc')
	    	->andWhere('u.id!=:auth_user')
	       	->andWhere('u.userType=:userType')
	       	->andWhere('u.isActive=:isActive')
	       	->andWhere('urc.company=:company')
	       	->setParameters(['auth_user' => $auth_user->getId(), 'userType' => 2, 'isActive' => 1, 'company' => $company]);

	    //echo $qb->getQuery()->getSQL();exit;

	    return $qb->getQuery()
	          ->getResult();
	}
}
