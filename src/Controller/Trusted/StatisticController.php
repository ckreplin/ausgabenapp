<?php

namespace App\Controller\Trusted;

use App\Entity\Category;
use App\Entity\Item;
use App\Service\DateHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/trusted/statistic")
 */
class StatisticController extends AbstractController
{
    private function getRepository()
    {
        return $this
            ->getDoctrine()
            ->getRepository(Item::class)
            ->setUser($this->getUser());
    }

    private function getCategoryRepository()
    {
        return $this
            ->getDoctrine()
            ->getRepository(Category::class)
            ->setUser($this->getUser());
    }

    /**
     * @Route("/", name="statistic_index", methods={"GET"})
     */
    public function index(DateHelper $dateHelper): Response
    {
        $items = $this
            ->getRepository()
            ->findBy(
                [
                    'user' => $this->getUser(),
                ],
                [
                    'dateAt' => 'ASC',
                ]
            );

        $work = [];
        $years = [];
        $itemsTime = [];
        $lastYear = 0;
        $lastMonth = 0;

        /** @var Item $item */
        foreach ($items as $item) {
            $work = [];
            $year = (int)$item->getDateAt()->format('Y');
            $month = (int)$item->getDateAt()->format('m');
            $newYear = $lastYear != $year;
            $newMonth = $lastMonth != $month;
            $newData = $newYear || $newMonth;

            if ($newYear) {
                array_push($years, $year);
            }

            if ($newData) {
                $yearMonth = $year . ': ' . $dateHelper->getMonthName($month);
                $work['display'] = $yearMonth;
                $work['year'] = $year;
                $work['month'] = $month;
                array_push($itemsTime, $work);

                $lastYear = $year;
                $lastMonth = $month;
            }
        }

        return $this->render(
            'trusted/statistic/index.html.twig',
            [
                'items' => $itemsTime,
                'years' => $years,
            ]
        );
    }

    /**
     * @Route("/{year}/{month}", name="statistic_show", methods={"GET"})
     */
    public function show($year, $month): Response
    {
        $incomeVsOutcome =
            $this
                ->getRepository()
                ->statisticsIncomeVsOutcome($year, $month);

        // all
        $incomeVsOutcomeBase = 0;
        $outgoAmount = $incomeVsOutcome[0]['amount'];
        $incomeAmount = $incomeVsOutcome[1]['amount'];
        if ($outgoAmount > $incomeAmount) {
            $incomeVsOutcomeBase = $outgoAmount;
        } else {
            $incomeVsOutcomeBase = $incomeAmount;
        }
        $incomeVsOutcome[0]['title'] = 'Ausgaben';
        $incomeVsOutcome[0]['percent'] = $outgoAmount / $incomeVsOutcomeBase * 100;
        $incomeVsOutcome[0]['barcolor'] = 'outgo';
        $incomeVsOutcome[1]['title'] = 'Einnahmen';
        $incomeVsOutcome[1]['percent'] = $incomeAmount / $incomeVsOutcomeBase * 100;
        $incomeVsOutcome[1]['barcolor'] = 'income';

        // outgo
        $outgoPerCategory = $this->getDoctrine()
            ->getRepository(Item::class)
            ->statisticsItemsPerCategory($year, $month, 0);
        $outgoPerCategoryBase = $outgoPerCategory[0]['amount'];
        foreach ($outgoPerCategory as $key => $item) {
            $outgoPerCategory[$key]['percent'] = $item['amount'] / $outgoPerCategoryBase * 100;
            $outgoPerCategory[$key]['barcolor'] = $outgoPerCategory[$key]['luxury'] ? 'luxury' : 'outgo';
        }

        // income
        $incomePerCategory = $this->getDoctrine()
            ->getRepository(Item::class)
            ->statisticsItemsPerCategory($year, $month, 1);
        $incomePerCategoryBase = $incomePerCategory[0]['amount'];
        foreach ($incomePerCategory as $key => $item) {
            $incomePerCategory[$key]['percent'] = $item['amount'] / $incomePerCategoryBase * 100;
            $incomePerCategory[$key]['barcolor'] = 'income';
        }

        return $this->render(
            'trusted/statistic/show.html.twig',
            [
                'incomeVsOutcome' => $incomeVsOutcome,
                'incomeVsOutcomeBase' => $incomeVsOutcomeBase,
                'outgoPerCategory' => $outgoPerCategory,
                'outgoPerCategoryBase' => $outgoPerCategoryBase,
                'incomePerCategory' => $incomePerCategory,
                'incomePerCategoryBase' => $incomePerCategoryBase,
                'year' => $year,
                'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            ]
        );
    }

    /**
     * @Route("/category/show/{year}", name="statistic_show_category", methods={"GET"})
     */
    public function showCategory($year): Response
    {
        $items = $this
            ->getRepository()
            ->statisticsCategoriesPerYear($year);

        $itemBase = $items[0]['amount'];
        foreach ($items as $key => $item) {
            $items[$key]['percent'] = $item['amount'] / $itemBase * 100;

            if ($items[$key]['income'] == '1') {
                $items[$key]['barcolor'] = 'income';
            } else {
                $items[$key]['barcolor'] = $items[$key]['luxury'] == '1' ? 'luxury' : 'outgo';
            }

            $items[$key]['link'] = $this->generateUrl(
                'statistic_show_category_year',
                [
                    'year' => $year,
                    'categoryId' => $items[$key]['category_id']
                ]
            );
        }

        return $this->render(
            'trusted/statistic/show_category.html.twig',
            [
                'year' => $year,
                'items' => $items,
            ]
        );
    }

    /**
     * @Route("/category/show/{year}/{categoryId}", name="statistic_show_category_year", methods={"GET"})
     */
    public function showCategoryPerYear($year, $categoryId, DateHelper $dateHelper): Response
    {
        $items = $this
            ->getRepository()
            ->statisticsCategoryPerYear($categoryId, $year);

        $categoryName = $this
            ->getCategoryRepository()
            ->findOneById($categoryId)
            ->getTitle();

        // get the base, highest amount
        $itemBase = 0;
        foreach ($items as $item) {
            if ($item['amount'] > $itemBase)
                $itemBase = $item['amount'];
        }

        foreach ($items as $key => $item) {
            $items[$key]['title'] = $dateHelper->getMonthName($item['item_month']);
            $items[$key]['percent'] = $item['amount'] / $itemBase * 100;
            if ($items[$key]['income'] == '1') {
                $items[$key]['barcolor'] = 'income';
            } else {
                $items[$key]['barcolor'] = $items[$key]['luxury'] == '1' ? 'luxury' : 'outgo';
            }
        }

        return $this->render(
            'trusted/statistic/show_category_year.html.twig',
            [
                'year' => $year,
                'items' => $items,
                'categoryName' => $categoryName,
            ]
        );
    }

    /**
     * @Route("/shared/items/all", name="statistic_show_shared", methods={"GET"})
     */
    public function showShared(DateHelper $dateHelper): Response
    {
        $itemsIncome = $this
            ->getRepository()
            ->statisticsSharedPerMonthYear(true);

        $itemsOutgo = $this
            ->getRepository()
            ->statisticsSharedPerMonthYear(false);

        $items = [];
        $income = 0;
        $outgo = 0;

        $ii = 0;
        foreach ($itemsIncome as $i => $item) {
            $items[$i]['title'] = sprintf(
                '%s %s',
                $dateHelper->getMonthName((int)substr($item['date_at'], 0,2)),
                substr($item['date_at'], 3)
            );

            $income = $item['amount'];
            if (count($itemsOutgo) > $i) {
                $outgo = $itemsOutgo[$i]['amount'];
            } else {
                $outgo = 0;
            }

            $base = $income > $outgo ? $income / 100 : $outgo / 100;

            $items[$i]['items'][$ii]['title'] = 'Einnahmen';
            $items[$i]['items'][$ii]['amount'] = $income;
            $items[$i]['items'][$ii]['barcolor'] = 'income';
            $items[$i]['items'][$ii]['percent'] = $income / $base;
            $ii++;

            $items[$i]['items'][$ii]['title'] = 'Ausgaben';
            $items[$i]['items'][$ii]['amount'] = $outgo;
            $items[$i]['items'][$ii]['barcolor'] = 'outgo';
            $items[$i]['items'][$ii]['percent'] = $outgo / $base;
            $ii++;

            $items[$i]['items'][$ii]['title'] = 'Differenz';
            $items[$i]['items'][$ii]['amount'] = $income - $outgo;
            if($items[$i]['items'][$ii]['amount'] < 0) {
                $items[$i]['items'][$ii]['barcolor'] = 'luxury';
                $items[$i]['items'][$ii]['percent'] = ($outgo - $income) / $base;
            } else {
                $items[$i]['items'][$ii]['barcolor'] = 'neutral';
                $items[$i]['items'][$ii]['percent'] = ($income - $outgo) / $base;
            }
        }
        $items = array_reverse ($items);

        return $this->render(
            'trusted/statistic/shared.html.twig',
            [
                'items' => $items,
            ]
        );
    }
}
