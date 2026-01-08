<?php
namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName;

class RecipeNewDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "El título es obligatorio")]
        public string $title,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[SerializedName('number-diner')] // Mapea "number-diner" del JSON
        public int $numberDiners,

        #[Assert\NotBlank]
        #[SerializedName('type-id')] // Mapea "type-id" del JSON
        public int $typeId,

        /** @var IngredientDTO[] */
        #[Assert\Valid] // Valida cada objeto IngredientDTO dentro del array
        #[Assert\Count(min: 1, minMessage: "Tienes que tener al menos 1 Ingrediente")]
        public array $ingredients,

        /** @var StepDTO[] */
        #[Assert\Valid]
        #[Assert\Count(min: 1, minMessage: "Tienen que tener al menos 1 Paso")]
        public array $steps,

        /** @var NutrientNewDTO[] */
        #[Assert\Valid]
        public array $nutrients
    ) {}
}