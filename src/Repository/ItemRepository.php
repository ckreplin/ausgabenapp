<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends BaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findOneById($id)
    {
        return $this
            ->findOneBy(
                [
                    'id' => $id,
                    'user' => $this->getUser()
                ]
            );
    }

    public function list($direction, $isShared)
    {
        $where = $isShared == '0' ? 'i.user = :user AND i.shared = 0' : 'i.shared = 1 AND :user = :user';

        return $this->createQueryBuilder('i')
            ->join('i.category','c')
            ->where('i.income = :income')
            ->andWhere('i.dateAt <= :today')
            ->andWhere($where)
            ->setParameter('user', $this->getUser())
            ->setParameter('income', $direction === 'income')
            ->setParameter('today', new \DateTime())
            ->addOrderBy('i.dateAt', 'desc')
            ->addOrderBy('c.title', 'asc')
            ->addOrderBy('i.title', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function listAll($direction, $isShared)
    {
        $where = $isShared == '0' ? 'i.user = :user AND i.shared = 0' : 'i.shared = 1 AND :user = :user';

        return $this->createQueryBuilder('i')
            ->join('i.category','c')
            ->where('i.income = :income')
            ->andWhere($where)
            ->setParameter('user', $this->getUser())
            ->setParameter('income', $direction === 'income')
            ->addOrderBy('i.dateAt', 'desc')
            ->addOrderBy('c.title', 'asc')
            ->addOrderBy('i.title', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function listByYearMonth($year, $month)
    {
        $start = sprintf('%d-%d-01 00:00:00', $year, $month);
        $end = date("Y-m-t", strtotime($start)) . ' 23:59:59';

        return $this->createQueryBuilder('i')
            ->join('i.category','c')
            ->where('i.dateAt >= :start')
            ->andWhere('i.dateAt <= :end')
            ->andWhere('i.user = :user')
            ->andWhere('i.shared = false')
            ->setParameter('user', $this->getUser())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->addOrderBy('i.income', 'desc')
            ->addOrderBy('i.dateAt', 'desc')
            ->addOrderBy('c.title', 'asc')
            ->addOrderBy('i.title', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function calculateCurrentBalance()
    {
        $sql = "SELECT 
                  (
                    SELECT sum(amount) 
                      FROM item 
                     WHERE income = 1 
                       AND date(date_at) <= CURDATE()
                       AND item.user_id = " . $this->getUser()->getId() . "
                       AND item.shared = false
                  ) income,
                  (
                    SELECT sum(amount) 
                      FROM item 
                     WHERE income = 0 
                       AND date(date_at) <= CURDATE()
                       AND user_id = " . $this->getUser()->getId() . "
                       AND item.shared = false
                   ) outgo,
                  (
                    SELECT sum(amount) 
                      FROM item 
                     WHERE income = 1
                       AND date(date_at) <= CURDATE()
                       AND shared = true
                   ) income_shared,
                  (
                    SELECT sum(amount) 
                      FROM item 
                     WHERE income = 0 
                       AND date(date_at) <= CURDATE()
                       AND item.shared = true
                   ) outgo_shared;";

        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql)
            ->fetchAll();
    }

    public function statisticsIncomeVsOutcome($year, $month)
    {
        $start = sprintf('%d-%d-01 00:00:00', $year, $month);
        $end = date("Y-m-t", strtotime($start)) . ' 23:59:59';

        $sql = "SELECT sum(amount) amount, income
                  FROM item
                 WHERE date_at >= CAST(:start AS DATETIME)
                   AND date_at <= CAST(:end AS DATETIME)
                   AND user_id = " . $this->getUser()->getId() . "
                   AND item.shared = false
              GROUP BY income
              ORDER BY income ASC;";
        $params = [
            'start' => $start,
            'end' => $end
        ];

        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params)
            ->fetchAll();
    }

    public function statisticsItemsPerCategory($year, $month, $income)
    {
        $start = sprintf('%d-%d-01 00:00:00', $year, $month);
        $end = date("Y-m-t", strtotime($start)) . ' 23:59:59';

        $sql = "SELECT sum(amount) amount, category.title, category.luxury
                  FROM item,
                       category
                 WHERE item.category_id = category.id
                   AND item.date_at >= CAST(:start AS DATETIME)
                   AND item.date_at <= CAST(:end AS DATETIME)
                   AND item.income = :income
                   AND item.user_id = " . $this->getUser()->getId() . "
                   AND item.shared = false
                 GROUP BY category.title
                 ORDER BY sum(item.amount) DESC,
                          category.title ASC;";
        $params = [
            'start' => $start,
            'end' => $end,
            'income' => $income
        ];

        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params)
            ->fetchAll();
    }

    public function statisticsCategoriesPerYear($year)
    {
        $start = sprintf('%d-01-01 00:00:00', $year);
        $end = sprintf('%d-12-31 23:59:59', $year);

        $sql = "SELECT sum(amount) amount,
                       category.title, 
                       category.luxury, 
                       category.income,
                       category.id category_id
                  FROM item,
                       category
                 WHERE item.category_id = category.id
                   AND date_at >= CAST(:start AS DATETIME)
                   AND date_at <= CAST(:end AS DATETIME)
                   AND item.user_id = " . $this->getUser()->getId() . "
                   AND item.shared = false
                 GROUP BY category.title
                 ORDER BY sum(item.amount) DESC,
                          category.title ASC;";
        $params = [
            'start' => $start,
            'end' => $end
        ];

        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params)
            ->fetchAll();
    }

    public function statisticsCategoryPerYear($category, $year)
    {
        $start = sprintf('%d-01-01 00:00:00', $year);
        $end = sprintf('%d-12-31 23:59:59', $year);

        $sql = "  SELECT month(item.date_at) item_month,
                         sum(item.amount) amount,
                         category.income income,
                         category.luxury luxury
                    FROM item,
                         category
                   WHERE item.category_id = category.id
                     AND category.id = :category_id
                     AND date_at >= CAST(:start AS DATETIME)
                     AND date_at <= CAST(:end AS DATETIME)
                     AND item.user_id = " . $this->getUser()->getId() . "
                     AND item.shared = false
                GROUP BY month(item.date_at)
                ORDER BY item.date_at ASC;";
        $params = [
            'start' => $start,
            'end' => $end,
            'category_id' => $category
        ];

        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params)
            ->fetchAll();
    }

    public function statisticsSharedPerMonthYear($income)
    {
        $sql = "SELECT sum(amount) amount,
                       DATE_FORMAT(CAST(date_at as DATE), '%m.%Y') date_at
                  FROM item
                 WHERE item.shared = true
                   AND item.income = :income 
                 GROUP BY DATE_FORMAT(CAST(date_at as DATE), '%m.%Y')
                 ORDER BY date_at ASC;";

        $params = [
            'income' => $income,
        ];

        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params)
            ->fetchAll();
    }
}
