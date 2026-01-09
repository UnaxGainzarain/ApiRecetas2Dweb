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
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use App\Entity\Rating; 
use App\Model\RatingNewDTO; 
use Symfony\Component\HttpFoundation\Request; 


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

    #[Route('/{id}', name: 'delete_recipe', methods: ['DELETE'])]
    public function deleteRecipe(string $id): JsonResponse // <--- CAMBIO AQUÍ: string en vez de int
    {
        // Convertimos el string "1" al número 1 manualmente para evitar el error
        $recipeId = (int) $id;

        // 1. Buscar la receta por ID
        $recipe = $this->recipeRepository->find($recipeId);

        // 2. Validar si existe
        if (!$recipe) {
            return $this->json(['error' => 'La receta con ID ' . $id . ' no existe.'], 404);
        }

        // 3. Borrado Lógico
        $recipe->setDeleted(true);

        // 4. Guardar cambios
        $this->entityManager->flush();

        // 5. Devolver respuesta
        return $this->json(['message' => 'La receta se ha eliminado correctamente (Borrado Lógico)']);
    }

    #[Route('/{id}/rating', name: 'rate_recipe', methods: ['POST'], format: 'json')]
    public function voteRecipe(
        string $id, 
        #[MapRequestPayload] RatingNewDTO $ratingDto,
        Request $request
    ): JsonResponse
    {
        $recipeId = (int) $id;

        // 1. Buscar la receta
        $recipe = $this->recipeRepository->find($recipeId);
        if (!$recipe) {
            return $this->json(['error' => 'La receta no existe'], 404);
        }

        // 2. Obtener la IP del cliente
        $clientIp = $request->getClientIp();
        // Nota: En local a veces sale "::1" (que es localhost en IPv6), es normal.

        // 3. Validar si esta IP ya ha votado esta receta
        // Usamos el EntityManager para acceder al repositorio de Rating sin inyectarlo en el constructor
        $existingRating = $this->entityManager->getRepository(Rating::class)->findOneBy([
            'recipe' => $recipe,
            'ip' => $clientIp
        ]);

        if ($existingRating) {
            return $this->json(['error' => 'Esta IP ya ha votado a esta receta'], 400);
        }

        // 4. Crear el voto
        $rating = new Rating();
        $rating->setScore($ratingDto->score);
        $rating->setIp($clientIp);
        $rating->setRecipe($recipe);

        // 5. Guardar
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Voto registrado correctamente',
            'score' => $rating->getScore(),
            'recipe_id' => $recipe->getId()
        ]);
    }
}