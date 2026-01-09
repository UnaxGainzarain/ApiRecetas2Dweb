<?php

namespace App\Controller;

use App\Repository\NutrientTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/nutrient-types')]
class NutrientTypeController extends AbstractController
{
    public function __construct(private NutrientTypeRepository $nutrientTypeRepository) {}

    #[Route('', name: 'get_nutrient_types', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $types = $this->nutrientTypeRepository->findAll();
        
        $data = [];
        foreach ($types as $type) {
            $data[] = [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'unit' => $type->getUnit(),
            ];
        }

        return $this->json($data);
    }
}