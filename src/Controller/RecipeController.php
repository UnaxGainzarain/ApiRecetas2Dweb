<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeNutrient;
use App\Entity\Step;
use App\Model\RecipeNewDTO;
use App\Repository\NutrientTypeRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload; // ¡Importante para el DTO!
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;


#[Route('/recipes')]
class RecipeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecipeRepository $recipeRepository,
        private RecipeTypeRepository $recipeTypeRepository,
        private NutrientTypeRepository $nutrientTypeRepository
    ) {}

    #[Route('', name: 'post_recipe', methods: ['POST'], format: 'json')]
    public function newRecipe(
        #[MapRequestPayload] RecipeNewDTO $recipeDto
    ): JsonResponse
    {
        // 1. Validar y buscar el Tipo de Receta
        $recipeType = $this->recipeTypeRepository->find($recipeDto->typeId);
        if (!$recipeType) {
            return $this->json(['error' => 'El tipo de receta (ID: ' . $recipeDto->typeId . ') no existe.'], 400);
        }

        // 2. Crear la Entidad Receta (Padre)
        $recipe = new Recipe();
        $recipe->setTitle($recipeDto->title);
        $recipe->setNumberDiners($recipeDto->numberDiners);
        $recipe->setDeleted(false); // Por defecto no está borrada
        $recipe->setType($recipeType);

        // Persistimos la receta para tenerla lista
        $this->entityManager->persist($recipe);

        // 3. Procesar Ingredientes (Relación 1-N)
        foreach ($recipeDto->ingredients as $ingDto) {
            $ingredient = new Ingredient();
            $ingredient->setName($ingDto->name);
            $ingredient->setQuantitiy($ingDto->quantity);
            $ingredient->setUnit($ingDto->unit);
            $ingredient->setRecipe($recipe); // Relacionamos con el padre

            $this->entityManager->persist($ingredient);
        }

        // 4. Procesar Pasos (Relación 1-N)
        foreach ($recipeDto->steps as $stepDto) {
            $step = new Step();
            $step->setDescription($stepDto->description);
            $step->setSterOrder($stepDto->order);
            $step->setRecipe($recipe);

            $this->entityManager->persist($step);
        }

        // 5. Procesar Nutrientes (Relación N-M con atributos -> Tabla Intermedia)
        foreach ($recipeDto->nutrients as $nutDto) {
            // Buscamos el tipo de nutriente en BBDD
            $nutrientType = $this->nutrientTypeRepository->find($nutDto->typeId);
            
            if (!$nutrientType) {
                return $this->json(['error' => 'El tipo de nutriente (ID: ' . $nutDto->typeId . ') no existe.'], 400);
            }

            $recipeNutrient = new RecipeNutrient();
            $recipeNutrient->setQuantity($nutDto->quantity);
            $recipeNutrient->setRecipe($recipe);
            $recipeNutrient->setNutrientType($nutrientType);

            $this->entityManager->persist($recipeNutrient);
        }

        // 6. Guardar todo en BBDD
        $this->entityManager->flush();

        // 7. Devolver respuesta
        return $this->json([
            'id' => $recipe->getId(),
            'title' => $recipe->getTitle(),
            'message' => 'Receta creada correctamente'
        ]);
    }

   

    #[Route('', name: 'get_recipes', methods: ['GET'])]
    public function searchRecipes(
        #[MapQueryParameter] ?int $type = null // Captura ?type=... de la URL
    ): JsonResponse
    {
        // 1. Buscamos las recetas en BBDD
        if ($type) {
            // Si viene el parámetro 'type', filtramos por ese tipo Y que no estén borradas
            $recipes = $this->recipeRepository->findBy(['type' => $type, 'deleted' => false]);
        } else {
            // Si no viene tipo, traemos todas las que NO estén borradas
            $recipes = $this->recipeRepository->findBy(['deleted' => false]);
        }

        // 2. Preparamos los datos para el JSON (Manual para evitar referencias circulares)
        $data = [];

        foreach ($recipes as $recipe) {
            
            // Preparamos ingredientes
            $ingredientsData = [];
            foreach ($recipe->getIngredients() as $ing) {
                $ingredientsData[] = [
                    'name' => $ing->getName(),
                    'quantity' => $ing->getQuantitiy(),
                    'unit' => $ing->getUnit()
                ];
            }

            // Preparamos pasos
            $stepsData = [];
            foreach ($recipe->getSteps() as $step) {
                $stepsData[] = [
                    'order' => $step->getSterOrder(),
                    'description' => $step->getDescription()
                ];
            }

            // Preparamos nutrientes
            $nutrientsData = [];
            foreach ($recipe->getRecipeNutrients() as $recNut) {
                $nutrientsData[] = [
                    // Ojo: según tu YAML el nutriente devuelve un objeto type y quantity
                    'id' => $recNut->getId(),
                    'quantity' => $recNut->getQuantity(),
                    'type' => [
                        'id' => $recNut->getNutrientType()->getId(),
                        'name' => $recNut->getNutrientType()->getName(),
                        'unit' => $recNut->getNutrientType()->getUnit(),
                    ]
                ];
            }

            // Preparamos valoraciones (calculamos la media al vuelo si quieres, o devolvemos null de momento)
            // Según el YAML, 'rating' es un objeto con 'number-votes' y 'rating-avg'.
            // Como esto requiere lógica extra, de momento lo dejaremos vacío o básico.
            $ratings = $recipe->getRatings();
            $avg = 0;
            if (count($ratings) > 0) {
                $sum = 0;
                foreach ($ratings as $r) { $sum += $r->getScore(); }
                $avg = $sum / count($ratings);
            }
            
            $ratingData = [
                'number-votes' => count($ratings),
                'rating-avg' => $avg
            ];


            // Montamos el objeto Receta completo
            $data[] = [
                'id' => $recipe->getId(),
                'title' => $recipe->getTitle(),
                'number-diner' => $recipe->getNumberDiners(),
                'type' => [
                    'id' => $recipe->getType()->getId(),
                    'name' => $recipe->getType()->getName(),
                    'description' => $recipe->getType()->getDescription(),
                ],
                'ingredients' => $ingredientsData,
                'steps' => $stepsData,
                'nutrients' => $nutrientsData,
                'rating' => $ratingData
            ];
        }

        // 3. Devolvemos la respuesta JSON
        return $this->json($data);
    }
}