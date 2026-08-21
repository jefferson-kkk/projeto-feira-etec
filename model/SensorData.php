<?php

class SensorData
{
    private $id;
    private $projectId;
    private $ppm;
    private $rawValue;
    private $createdAt;

    public function __construct(
        $id = null,
        $projectId = null,
        $ppm = 0,
        $rawValue = null,
        $createdAt = null
    ) {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->ppm = $ppm;
        $this->rawValue = $rawValue;
        $this->createdAt = $createdAt;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function getPpm()
    {
        return $this->ppm;
    }

    public function getRawValue()
    {
        return $this->rawValue;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}