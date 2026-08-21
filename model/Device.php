<?php

class Device
{
    private $id;
    private $userId;
    private $name;
    private $manufacturerCode;
    private $description;
    private $locationId;
    private $sensorType;
    private $status;
    private $firmwareVersion;
    private $wifiStatus;
    private $esp32Id;
    private $lastOnline;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id,
        $userId,
        $name,
        $manufacturerCode,
        $description = null,
        $locationId = null,
        $sensorType = 'TGS2610',
        $status = 'offline',
        $firmwareVersion = null,
        $wifiStatus = null,
        $esp32Id = null,
        $lastOnline = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->manufacturerCode = $manufacturerCode;
        $this->description = $description;
        $this->locationId = $locationId;
        $this->sensorType = $sensorType;
        $this->status = $status;
        $this->firmwareVersion = $firmwareVersion;
        $this->wifiStatus = $wifiStatus;
        $this->esp32Id = $esp32Id;
        $this->lastOnline = $lastOnline;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getManufacturerCode()
    {
        return $this->manufacturerCode;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getLocationId()
    {
        return $this->locationId;
    }

    public function getSensorType()
    {
        return $this->sensorType;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getFirmwareVersion()
    {
        return $this->firmwareVersion;
    }

    public function getWifiStatus()
    {
        return $this->wifiStatus;
    }

    public function getEsp32Id()
    {
        return $this->esp32Id;
    }

    public function getLastOnline()
    {
        return $this->lastOnline;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}