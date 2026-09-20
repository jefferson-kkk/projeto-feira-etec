<?php

class Location
{
    private $id;
    private $userId;
    private $name;
    private $description;
    private $sector;
    private $floor;
    private $createdAt;
    private $updatedAt;
    private $posX;
    private $posY;
    private $width;
    private $depth;

    /*
     * posX/posY/width/depth descrevem o retângulo do cômodo na planta
     * (metros, ponto 0,0 no canto superior esquerdo), definido pelo
     * usuário no editor de planta. São usados para desenhar a cena 3D.
     */
    public function __construct(
        $id,
        $userId,
        $name,
        $description = null,
        $sector = null,
        $floor = null,
        $createdAt = null,
        $updatedAt = null,
        $posX = 0,
        $posY = 0,
        $width = 3,
        $depth = 3
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->description = $description;
        $this->sector = $sector;
        $this->floor = $floor;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->posX = $posX;
        $this->posY = $posY;
        $this->width = $width;
        $this->depth = $depth;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getSector() { return $this->sector; }
    public function getFloor() { return $this->floor; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getPosX() { return $this->posX; }
    public function getPosY() { return $this->posY; }
    public function getWidth() { return $this->width; }
    public function getDepth() { return $this->depth; }
}