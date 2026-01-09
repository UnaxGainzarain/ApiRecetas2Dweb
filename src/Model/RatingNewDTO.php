<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RatingNewDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "La puntuación es obligatoria")]
        #[Assert\Range(
            min: 0, 
            max: 5, 
            notInRangeMessage: "La puntuación debe estar entre {{ min }} y {{ max }}"
        )]
        public int $score
    ) {}
}