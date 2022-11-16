<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'title', length: 255, type: 'string', unique: true)]
    private string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: 'true')]
    private string $description;

    #[ORM\Column(name: 'picture', length: 1200, type: 'string')]
    private string $picture;

    #[ORM\Column(name: 'created_at', type: 'string')]
    private string $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'string', nullable: 'true')]
    private string $updateddAt;

    /**
     * Create the getters and setters function
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title)
    {
        return $this->title = $title;
    }

    public function gettitle(): string
    {
        return $this->title;
    }

    public function setDescription(string $description)
    {
        return $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setPicture(string $picture)
    {
        return $this->picture = $picture;
    }

    public function getPicture(): string
    {
        return $this->picture;
    }

    public function setCreatedAt(string $createdAt)
    {
        return $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

}
