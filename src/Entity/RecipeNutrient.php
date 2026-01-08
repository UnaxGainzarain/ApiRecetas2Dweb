<?php

namespace App\Entity;

use App\Repository\RecipeNutrientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeNutrientRepository::class)]
class RecipeNutrient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'recipeNutrients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $recipe = null;

    #[ORM\ManyToOne(inversedBy: 'recipeNutrients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NutrientType $nutrientType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getNutrientType(): ?NutrientType
    {
        return $this->nutrientType;
    }

    public function setNutrientType(?NutrientType $nutrientType): static
    {
        $this->nutrientType = $nutrientType;

        return $this;
    }
}
