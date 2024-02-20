<?php

namespace App\Controller;

use App\Entity\Article\Article;
use App\Entity\Price\Deal\Deal;
use App\Repository\Article\ArticleRepository;
use App\Repository\ArticleGroup\ArticleGroupRepository;
use App\Repository\Price\Deal\DealRepository;
use App\Repository\Price\Rate\EntryRepository;
use App\Repository\Price\Rate\GroupRepository;
use Doctrine\ORM\EntityManager;
use phpDocumentor\Reflection\Types\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PriceCalculationController extends AbstractController
{

    #[Route('/articles/pricecalculation.json', name: 'article_price_calculation')]
    public function article(Request $request, EntryRepository $priceRateRepository, ArticleRepository $articleRepository, DealRepository $dealRepository): Response
    {

        $articleIds = $request->query->all('articleIds');
        $rentalStart = $request->query->get('rentalStart');
        $rentalEnd = $request->query->get('rentalEnd');
        $msOnlineBookingOnly = (!empty($request->query->get('msOnlineBookingOnly')) ? $request->query->get('msOnlineBookingOnly') : false);

        $returnData = [];

        foreach ($articleIds as $articleId) {

            # PRICE RATES
            $listArray = [];
            $priceTotal = 0;
            $kmhTotal = 0;

            $article = $articleRepository->find($articleId);

            $rentalStartCalculation = $rentalStart;
            $rentalEndCalculation = $rentalEnd - 1;

            $rentalDays = $this->calculateRentalDays($rentalStartCalculation, $rentalEndCalculation);
            $rentalHours = round(($rentalEndCalculation - $rentalStartCalculation) / 60 / 60);

            switch ($article->getDefaultPriceCalculation()) {

                # CALCULATE DAY RATES
                case Article::DEFAULT_PRICE_CALCULATION_RATES_DAY:

                    $priceConfig = [];
                    $priceConfig['calculationType'] = "per_day";
                    $priceConfig['calculationPriceType'] = "rates";

                    while ($rentalStartCalculation <= $rentalEndCalculation) {

                        $middleOfTheDay = new \DateTime();
                        $middleOfTheDay->setTimestamp(mktime(12, 0, 0, date("m", $rentalStartCalculation), date("d", $rentalStartCalculation), date("Y", $rentalStartCalculation)));

                        $priceRateResults = $priceRateRepository->findForArticleBetweenStartAndRentaEnd($articleId, $middleOfTheDay, $rentalDays['calculationDays']);
                        $priceRateArray = [];

                        foreach ($priceRateResults as $priceRateResult) {
                            $priceRateArray = $priceRateResult;
                        }

                        if (sizeof($priceRateResults) != 0) {
                            $listArray[date("d.m.Y", $rentalStartCalculation)] = $priceRateArray;
                            $priceTotal += $priceRateArray['price'];
                            $kmhTotal += $priceRateArray['unitFree'];
                        }

                        $rentalStartCalculation = strtotime("+1 day", $rentalStartCalculation);
                    }

                    break;

                # CALCULATE DAY RATES FIX
                case Article::DEFAULT_PRICE_CALCULATION_RATES_DAY_FIX:

                    $priceConfig = [];
                    $priceConfig['calculationType'] = "per_day";
                    $priceConfig['calculationPriceType'] = "rates_fix";

                    $middleOfTheDay = new \DateTime();
                    $middleOfTheDay->setTimestamp(mktime(12, 0, 0, date("m", $rentalStartCalculation), date("d", $rentalStartCalculation), date("Y", $rentalStartCalculation)));

                    $priceRateResults = $priceRateRepository->findForArticleBetweenStartAndRentaEnd($articleId, $middleOfTheDay, $rentalDays['calculationDays']);

                    if (isset($priceRateResults) && sizeof($priceRateResults) > 0) {
                        $priceRateResults = $priceRateResults[0];
                        $priceTotal = $priceRateResults['price'];
                    }

                    $listArray = [];

                    break;

                # CALCULATE FIX PRICES
                case Article::DEFAULT_PRICE_CALCULATION_FIX:

                    $priceConfig = [];
                    $priceConfig['calculationType'] = "";
                    $priceConfig['calculationPriceType'] = "fix";

                    $priceTotal = $article->getPriceFix();
                    break;
            }

            # PRICE DEALS
            $date = new \DateTime();
            $date->setTimestamp($rentalStart);

            $dealResults = $dealRepository->findAllBetweenDate($articleId, $date, $msOnlineBookingOnly);
            $dealArray = array();

            if (isset($dealResults) && sizeof($dealResults) >= 1) {
                /** @var Deal $dealResult */
                foreach ($dealResults as $dealResult) {

                    if ($dealResult->getDealBase() == Deal::DEAL_BASE_HOUR && $dealResult->getDealSpecification() == Deal::DEAL_SSPECIFICATION_TIME) {

                        $startTimeSeconds = date("H", $rentalStartCalculation);
                        $startTimeSeconds = $startTimeSeconds * 60 * 60;
                        $startTimeSeconds = $startTimeSeconds + date("i", $rentalStartCalculation);

                        if ($startTimeSeconds >= $dealResult->getSpec10Start() && $dealResult->getSpec10MaxHours() >= $rentalHours && $dealResult->getSpec10ValidDays() == date("N", $rentalStart)) {
                            $dealArray[] = array(
                                'id' => $dealResult->getId(),
                                'title' => $dealResult->getName(),
                                'price' => $dealResult->getPrice()
                            );
                        }
                    }

                    if ($dealResult->getDealBase() == Deal::DEAL_BASE_HOUR && $dealResult->getDealSpecification() == Deal::DEAL_SSPECIFICATION_LENGTH) {

                        if ($dealResult->getSpec20HourStart() <= $rentalHours && $dealResult->getSpec20HourEnd() >= $rentalHours) {
                            $dealArray[] = array(
                                'id' => $dealResult->getId(),
                                'title' => $dealResult->getName(),
                                'price' => $dealResult->getPrice()
                            );
                        }
                    }
                }
            }

            $d = [];
            $d['id'] = $articleId;

            $price = [];
            $price['config'] = $priceConfig;

            $priceTotalArray = [];
            $priceTotalArray['brutto'] = $priceTotal;
            $price['total'] = $priceTotalArray;

            $kmhArray = [];
            $kmhArray['total'] = $kmhTotal;
            $price['kmh'] = $kmhArray;

            $priceLists = [];
            $priceListsDaily = [];
            $priceCumulated = [];
            $priceCumulatedDaily = [];

            foreach ($listArray as $key => $value) {

                // Daily
                $a = [];
                $a['date'] = $key;

                foreach ($value as $keyDaily => $valueDaily) {
                    $a[$keyDaily] = $valueDaily;
                }

                if (!isset($priceCumulated[$value['groupId']]['days'])) {
                    $priceCumulated[$value['groupId']]['days'] = 0;
                    $priceCumulated[$value['groupId']]['unitPrice'] = 0;
                }

                $priceCumulated[$value['groupId']]['name'] = $value['group_name'];
                $priceCumulated[$value['groupId']]['days'] = $priceCumulated[$value['groupId']]['days'] + 1;
                $priceCumulated[$value['groupId']]['unitPrice'] = $value['price'];
                $priceCumulated[$value['groupId']]['kmh'] = $value['unitFree'];

                $priceListsDaily[] = $a;
            }

            foreach ($priceCumulated as $keyArticle => $value) {

                $priceCumulatedDaily[] = $value;

            }

            $priceLists['daily'] = $priceListsDaily;
            $priceLists['cumulated'] = $priceCumulatedDaily;
            $price['lists'] = $priceLists;
            $d['price'] = $price;

            $data = [];
            $data['rentalStart'] = $rentalStart;
            $data['rentalEnd'] = $rentalStart;
            $data['rentalDays'] = $rentalDays['rentalDays'];
            $data['rentalHours'] = $rentalHours;
            $data['calculationDays'] = $rentalDays['calculationDays'];
            $d['data'] = $data;
            $d['deals'] = $dealArray;

            $returnData[] = $d;
        }


        $response = new Response(json_encode($returnData));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    #[Route('/article-groups/pricecalculation.json', name: 'article_group_price_calculation')]
    public function articleGroup(Request $request, EntryRepository $priceRateRepository, ArticleGroupRepository $articleGroupRepository): Response
    {

        $articleGroupIds = $request->query->all('articleGroupIds');
        $rentalStart = $request->query->get('rentalStart');
        $rentalEnd = $request->query->get('rentalEnd');

//        $msOnlineBookingOnly = (!empty($request->query->get('msOnlineBookingOnly')) ? $request->query->get('msOnlineBookingOnly') : false);

        $returnData = [];

        foreach ($articleGroupIds as $articleGroupId) {

            # PRICE RATES
            $listArray = [];
            $priceTotal = 0;
            $kmhTotal = 0;

           // $articleGroup = $articleGroupRepository->find($articleGroupId);

            $rentalStartCalculation = $rentalStart;
            $rentalEndCalculation = $rentalEnd - 1;

            $rentalDays = $this->calculateRentalDays($rentalStartCalculation, $rentalEndCalculation);
            $rentalHours = round(($rentalEndCalculation - $rentalStartCalculation) / 60 / 60);


            $priceConfig = [];
            $priceConfig['calculationType'] = "per_day";
            $priceConfig['calculationPriceType'] = "rates";

            while ($rentalStartCalculation <= $rentalEndCalculation) {

                $middleOfTheDay = new \DateTime();
                $middleOfTheDay->setTimestamp(mktime(12, 0, 0, date("m", $rentalStartCalculation), date("d", $rentalStartCalculation), date("Y", $rentalStartCalculation)));

                $priceRateResults = $priceRateRepository->findForArticleGroupBetweenStartAndRentaEnd($articleGroupId, $middleOfTheDay, $rentalDays['calculationDays']);
                $priceRateArray = [];

                foreach ($priceRateResults as $priceRateResult) {
                    $priceRateArray = $priceRateResult;
                }

                if (sizeof($priceRateResults) != 0) {
                    $listArray[date("d.m.Y", $rentalStartCalculation)] = $priceRateArray;
                    $priceTotal += $priceRateArray['price'];
                    $kmhTotal += $priceRateArray['unitFree'];
                }

                $rentalStartCalculation = strtotime("+1 day", $rentalStartCalculation);
            }


            $d = [];
            $d['id'] = $articleGroupId;

            $price = [];
            $price['config'] = $priceConfig;

            $priceTotalArray = [];
            $priceTotalArray['brutto'] = $priceTotal;
            $price['total'] = $priceTotalArray;

            $kmhArray = [];
            $kmhArray['total'] = $kmhTotal;
            $price['kmh'] = $kmhArray;

            $priceLists = [];
            $priceListsDaily = [];
            $priceCumulated = [];
            $priceCumulatedDaily = [];

            foreach ($listArray as $key => $value) {

                // Daily
                $a = [];
                $a['date'] = $key;

                foreach ($value as $keyDaily => $valueDaily) {
                    $a[$keyDaily] = $valueDaily;
                }

                if (!isset($priceCumulated[$value['groupId']]['days'])) {
                    $priceCumulated[$value['groupId']]['days'] = 0;
                    $priceCumulated[$value['groupId']]['unitPrice'] = 0;
                }

                $priceCumulated[$value['groupId']]['name'] = $value['group_name'];
                $priceCumulated[$value['groupId']]['days'] = $priceCumulated[$value['groupId']]['days'] + 1;
                $priceCumulated[$value['groupId']]['unitPrice'] = $value['price'];
                $priceCumulated[$value['groupId']]['kmh'] = $value['unitFree'];

                $priceListsDaily[] = $a;
            }

            foreach ($priceCumulated as $keyArticle => $value) {

                $priceCumulatedDaily[] = $value;

            }

            $priceLists['daily'] = $priceListsDaily;
            $priceLists['cumulated'] = $priceCumulatedDaily;
            $price['lists'] = $priceLists;
            $d['price'] = $price;

            $data = [];
            $data['rentalStart'] = $rentalStart;
            $data['rentalEnd'] = $rentalStart;
            $data['rentalDays'] = $rentalDays['rentalDays'];
            $data['rentalHours'] = $rentalHours;
            $data['calculationDays'] = $rentalDays['calculationDays'];
            $d['data'] = $data;

            $returnData[] = $d;
        }

        $response = new Response(json_encode($returnData));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function calculateRentalDays(int $rentalStartCalculation, int $rentalEnd): array
    {
        $rentalDays = 0;

        while ($rentalStartCalculation <= $rentalEnd) {
            $rentalDays++;
            $rentalStartCalculation = strtotime("+1 day", $rentalStartCalculation);
        }

        $calculationDays = $rentalDays;

        return [
            'rentalDays' => $rentalDays,
            'calculationDays' => $calculationDays,
        ];
    }

}
