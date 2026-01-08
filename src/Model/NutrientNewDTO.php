<?php
namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName; // <--- ¡ESTA LÍNEA ES CLAVE!

class NutrientNewDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[SerializedName('type-id')] // Mapea el campo "type-id" del JSON
        public int $typeId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public float $quantity
    ) {}
}