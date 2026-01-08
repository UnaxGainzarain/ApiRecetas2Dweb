<?php
namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class StepDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $order,

        #[Assert\NotBlank(message: "La descripción del paso es obligatoria")]
        public string $description
    ) {}
}