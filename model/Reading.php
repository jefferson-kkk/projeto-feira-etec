<?php

class Reading
{
    private $id;
    private $deviceId;
    private $ppm;
    private $status;
    private $wifiRssi;
    private $createdAt;

    public function __construct(
        $id,
        $deviceId,
        $ppm,
        $status,
        $wifiRssi = null,
        $createdAt = null
    ) {
        $this->id = $id;
        $this->deviceId = $deviceId;
        $this->ppm = $ppm;
        $this->status = $status;
        $this->wifiRssi = $wifiRssi;
        $this->createdAt = $createdAt;
    }

    public function getId() { return $this->id; }
    public function getDeviceId() { return $this->deviceId; }
    public function getPpm() { return $this->ppm; }
    public function getStatus() { return $this->status; }
    public function getWifiRssi() { return $this->wifiRssi; }
    public function getCreatedAt() { return $this->createdAt; }
}