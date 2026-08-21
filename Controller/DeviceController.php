<?php

require_once __DIR__ . '/../model/DeviceDAO.php';

class DeviceController
{
    private $deviceDAO;

    public function __construct()
    {
        $this->deviceDAO = new DeviceDAO();
    }

    public function getById($id, $userId = null)
    {
        return $this->deviceDAO->getById($id, $userId);
    }

    public function getByEsp32Id($esp32Id)
    {
        return $this->deviceDAO->getByEsp32Id($esp32Id);
    }

    public function getAllByUser($userId)
    {
        return $this->deviceDAO->getAllByUser($userId);
    }

    public function createDevice(
        $userId,
        $name,
        $manufacturerCode,
        $esp32Id,
        $locationId = null,
        $description = null,
        $firmwareVersion = null,
        $sensorType = 'MQ135'
    ) {
        if ($name === '') {
            throw new Exception('Nome do dispositivo é obrigatório.');
        }

        if ($manufacturerCode === '') {
            throw new Exception('Código do fabricante é obrigatório.');
        }

        if ($esp32Id === '') {
            throw new Exception('ESP32 ID é obrigatório.');
        }

        if ($this->deviceDAO->getByEsp32Id($esp32Id)) {
            throw new Exception(
                'Esse ESP32 já está cadastrado.'
            );
        }

        if ($this->deviceDAO->getByManufacturerCode($manufacturerCode)) {
            throw new Exception(
                'Esse código do fabricante já está cadastrado.'
            );
        }

        /*
         * Gera a chave que será entregue ao usuário.
         * Apenas o hash é salvo no banco.
         */
        $apiKey = bin2hex(
            random_bytes(24)
        );

        $deviceId = $this->deviceDAO->create([
            ':user_id' => $userId,
            ':name' => $name,
            ':manufacturer_code' => $manufacturerCode,
            ':description' => $description,
            ':location_id' => $locationId,
            ':sensor_type' => $sensorType,
            ':status' => 'offline',
            ':firmware_version' => $firmwareVersion,
            ':wifi_status' => null,
            ':esp32_id' => $esp32Id,
            ':api_key_hash' => password_hash(
                $apiKey,
                PASSWORD_DEFAULT
            )
        ]);

        return [
            'device_id' => $deviceId,
            'api_key' => $apiKey
        ];
    }

    public function updateDevice(
        $userId,
        $id,
        $name,
        $manufacturerCode,
        $esp32Id,
        $locationId = null,
        $description = null,
        $status = 'offline',
        $firmwareVersion = null,
        $sensorType = 'MQ135'
    ) {
        $device = $this->deviceDAO->getById(
            $id,
            $userId
        );

        if (!$device) {
            throw new Exception(
                'Dispositivo não encontrado.'
            );
        }

        if ($manufacturerCode === '') {
            throw new Exception(
                'Código do fabricante é obrigatório.'
            );
        }

        if ($esp32Id === '') {
            throw new Exception(
                'ESP32 ID é obrigatório.'
            );
        }

        $otherDevice =
            $this->deviceDAO->getByManufacturerCode(
                $manufacturerCode
            );

        if (
            $otherDevice &&
            (int)$otherDevice->getId() !== (int)$id
        ) {
            throw new Exception(
                'Esse código do fabricante já pertence a outro dispositivo.'
            );
        }

        $otherEsp =
            $this->deviceDAO->getByEsp32Id(
                $esp32Id
            );

        if (
            $otherEsp &&
            (int)$otherEsp->getId() !== (int)$id
        ) {
            throw new Exception(
                'Esse ESP32 ID já pertence a outro dispositivo.'
            );
        }

        return $this->deviceDAO->update([
            ':id' => $id,
            ':user_id' => $userId,
            ':name' => $name,
            ':manufacturer_code' => $manufacturerCode,
            ':description' => $description,
            ':location_id' => $locationId,
            ':sensor_type' => $sensorType,
            ':status' => $status,
            ':firmware_version' => $firmwareVersion,
            ':esp32_id' => $esp32Id
        ]);
    }

    public function deleteDevice($id, $userId)
    {
        return $this->deviceDAO->delete(
            $id,
            $userId
        );
    }
}