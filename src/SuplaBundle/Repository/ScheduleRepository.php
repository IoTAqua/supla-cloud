<?php
namespace SuplaBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use SuplaBundle\Entity\Schedule;

class ScheduleRepository extends EntityRepository {
    /** @return Schedule[] */
    public function findByQuery(ScheduleListQuery $query): array {
        $criteria = Criteria::create();
        if ($query->getUser()) {
            $criteria->where(Criteria::expr()->eq('user', $query->getUser()));
        }
        if ($query->getChannel()) {
            $criteria->where(Criteria::expr()->eq('channel', $query->getChannel()));
        }
        if ($query->getOrderBy()) {
            $criteria->orderBy($query->getOrderBy());
        }
        return $this->matching($criteria)->toArray();
    }
}
