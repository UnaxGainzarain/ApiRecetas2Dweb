<?php

namespace App\Controller;

use App\Repository\RecipeTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recipe-types')]
class RecipeTypeController extends AbstractController
{
    public function __construct(private RecipeTypeRepository $recipeTypeRepository) {}

    #[Route('', name: 'get_recipe_types', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $types = $this->recipeTypeRepository->findAll();
        
        $data = [];
        foreach ($types as $type) {
            $data[] = [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'description' => $type->getDescription(),
            ];
        }

        return $this->json($data);
    }
}