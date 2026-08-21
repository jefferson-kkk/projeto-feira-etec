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

    public function __construct(
        $id,
        $userId,
        $name,
        $description = null,
        $sector = null,
        $floor = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->description = $description;
        $this->sector = $sector;
        $this->floor = $floor;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getSector() { return $this->sector; }
    public function getFloor() { return $this->floor; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
}