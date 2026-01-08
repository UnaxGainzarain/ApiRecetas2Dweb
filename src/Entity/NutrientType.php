<?php

namespace App\Entity;

use App\Repository\NutrientTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NutrientTypeRepository::class)]
class NutrientType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $unit = null;

    /**
     * @var Collection<int, RecipeNutrient>
     */
    #[ORM\OneToMany(targetEntity: RecipeNutrient::class, mappedBy: 'nutrientType')]
    private Collection $recipeNutrients;

    public function __construct()
    {
        $this->recipeNutrients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return Collection<int, RecipeNutrient>
     */
    public function getRecipeNutrients(): Collection
    {
        return $this->recipeNutrients;
    }

    public function addRecipeNutrient(RecipeNutrient $recipeNutrient): static
    {
        if (!$this->recipeNutrients->contains($recipeNutrient)) {
            $this->recipeNutrients->add($recipeNutrient);
            $recipeNutrient->setNutrientType($this);
        }

        return $this;
    }

    public function removeRecipeNutrient(RecipeNutrient $recipeNutrient): static
    {
        if ($this->recipeNutrients->removeElement($recipeNutrient)) {
            // set the owning side to null (unless already changed)
            if ($recipeNutrient->getNutrientType() === $this) {
                $recipeNutrient->setNutrientType(null);
            }
        }

        return $this;
    }
}
