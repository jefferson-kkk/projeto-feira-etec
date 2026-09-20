<?php

require_once __DIR__ . '/../model/LocationDAO.php';
require_once __DIR__ . '/../model/Location.php';

class LocationController
{
    private $locationDAO;

    public function __construct()
    {
        $this->locationDAO = new LocationDAO();
    }

    public function getByUser($userId)
    {
        return $this->locationDAO->getByUser($userId);
    }

    public function getById($id, $userId = null)
    {
        return $this->locationDAO->getById(
            $id,
            $userId
        );
    }

    public function create(
        $userId,
        $name,
        $description = null,
        $sector = null,
        $floor = null,
        $posX = 0,
        $posY = 0,
        $width = 3,
        $depth = 3
    ) {
        $location = new Location(
            null,
            $userId,
            $name,
            $description,
            $sector,
            $floor,
            null,
            null,
            $posX,
            $posY,
            $width,
            $depth
        );

        return $this->locationDAO->create(
            $location
        );
    }

    public function updateLayout($id, $userId, $posX, $posY, $width, $depth)
    {
        return $this->locationDAO->updateLayout($id, $userId, $posX, $posY, $width, $depth);
    }

    public function update(
        $userId,
        $id,
        $name,
        $description = null,
        $sector = null,
        $floor = null
    ) {
        $location = new Location(
            $id,
            $userId,
            $name,
            $description,
            $sector,
            $floor
        );

        return $this->locationDAO->update(
            $location
        );
    }

    public function delete($id, $userId)
    {
        return $this->locationDAO->delete(
            $id,
            $userId
        );
    }
}