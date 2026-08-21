<?php

require_once __DIR__ . '/../model/ProjectDAO.php';

class ProjectController
{
    private $projectDAO;

    public function __construct()
    {
        $this->projectDAO = new ProjectDAO();
    }

    public function getProjectsByUser($userId)
    {
        return $this->projectDAO->getProjectsByUser($userId);
    }

    public function countProjectsByUser($userId)
    {
        return $this->projectDAO->countProjectsByUser($userId);
    }

    public function getProjectByCode($manufacturerCode)
    {
        return $this->projectDAO->getProjectByCode($manufacturerCode);
    }

    public function getProjectById($id)
    {
        return $this->projectDAO->getProjectById($id);
    }

    public function getProjectByApiKey($apiKey)
    {
        return $this->projectDAO->getProjectByApiKey($apiKey);
    }

    public function createProject(
        $userId,
        $name,
        $manufacturerCode,
        $firmwareVersion = null,
        $wifiStatus = null,
        $esp32Id = null,
        $lastOnline = null,
        $location = null,
        $description = null
    ) {
        $project = new Project(
            null,
            $userId,
            $name,
            $manufacturerCode,
            date('Y-m-d H:i:s'),
            'offline',
            $firmwareVersion,
            $wifiStatus,
            $esp32Id,
            $lastOnline,
            $location,
            $description
        );

        return $this->projectDAO->createProject($project);
    }

    public function atualizarProjeto(
        $id,
        $name,
        $location,
        $description,
        $firmwareVersion,
        $wifiStatus
    ) {
        return $this->projectDAO->atualizarProjeto(
            $id,
            $name,
            $location,
            $description,
            $firmwareVersion,
            $wifiStatus
        );
    }

    public function atualizarStatus($id, $status, $wifiStatus = null)
    {
        return $this->projectDAO->atualizarStatus(
            $id,
            $status,
            $wifiStatus
        );
    }
}