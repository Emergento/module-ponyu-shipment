<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Api;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;

interface ShipmentManagementInterface
{

    /**
     * Create a shipment and return the slot information related to the shipment
     *
     * @param ShipmentInterface $shipment
     * @return array
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function createShipment(ShipmentInterface $shipment): array;

}
