<?php

namespace App\Controller;

use App\Entity\Settings\Category;
use App\Repository\Settings\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class GetCategoriesController extends AbstractController
{

    #[Route( '/get-categories.json', name: 'get_categories' )]
    public function index(Request $request, CategoryRepository $categoryRepository) : Response
    {
        $idClient = $request->get('clientId');

//        $returnArray = [1 => [2 => [3,4]]];
        $return = $categoryRepository->findBy(['clientId' => $idClient, 'enableOnlineBooking' => true], ['root' => 'ASC', 'lft' => 'ASC']);
//        dd($return);

        $serializer = new Serializer(
            [ new ObjectNormalizer() ],
            [ new JsonEncoder() ]
        );

        $arr = [];
        foreach ($return as $category){
            $serialized = $serializer->normalize($category, null, [AbstractNormalizer::ATTRIBUTES => ['id','lvl','name']]);

            $arr[] = $serialized;
        }

//        dd($arr);


        $options = ['decorate' => false];
//        $array = $categoryRepository->childrenHierarchy();
//        dd($arr);

        $tree = $categoryRepository->buildTree($arr, $options);
//        dd($tree);

        $serializer = new Serializer(
            [ new GetSetMethodNormalizer(), new ArrayDenormalizer() ],
            [ new JsonEncoder() ]
        );

//        dd(json_encode($tree));

        $data = $serializer->deserialize(json_encode($tree), Category::class . '[]', 'json');
//        dd($tree);

//        $array = new ArrayCollection($tree);

//        dd($tree);

        $response = new Response(json_encode($tree));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
