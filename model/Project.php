<?php

class Project
{
    private $id;
    private $userId;
    private $name;
    private $manufacturerCode;
    private $registeredAt;
    private $status;
    private $firmwareVersion;
    private $wifiStatus;
    private $esp32Id;
    private $lastOnline;
    private $location;
    private $description;
    private $apiKey;

    public function __construct(
        $id = null,
        $userId = null,
        $name = '',
        $manufacturerCode = '',
        $registeredAt = null,
        $status = 'offline',
        $firmwareVersion = null,
        $wifiStatus = null,
        $esp32Id = null,
        $lastOnline = null,
        $location = null,
        $description = null,
        $apiKey = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->manufacturerCode = $manufacturerCode;
        $this->registeredAt = $registeredAt;
        $this->status = $status;
        $this->firmwareVersion = $firmwareVersion;
        $this->wifiStatus = $wifiStatus;
        $this->esp32Id = $esp32Id;
        $this->lastOnline = $lastOnline;
        $this->location = $location;
        $this->description = $description;
        $this->apiKey = $apiKey;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getName() { return $this->name; }
    public function getManufacturerCode() { return $this->manufacturerCode; }
    public function getRegisteredAt() { return $this->registeredAt; }
    public function getStatus() { return $this->status; }
    public function getFirmwareVersion() { return $this->firmwareVersion; }
    public function getWifiStatus() { return $this->wifiStatus; }
    public function getEsp32Id() { return $this->esp32Id; }
    public function getLastOnline() { return $this->lastOnline; }
    public function getLocation() { return $this->location; }
    public function getDescription() { return $this->description; }
    public function getApiKey() { return $this->apiKey; }
}