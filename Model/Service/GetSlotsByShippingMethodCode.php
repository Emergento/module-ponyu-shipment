<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Service;

use Emergento\PonyU\Model\GetDeliverySlots;
use Emergento\PonyUShippingMethod\Model\Config;
use Emergento\PonyUShippingMethod\Model\GetSenderCoordinates;
use GuzzleHttp\Exception\GuzzleException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;

/**
 * Retrieve available time slots for a given shipping method
 * @api
 */
class GetSlotsByShippingMethodCode
{

    public function __construct(
        private readonly GetDeliverySlots $getDeliverySlots,
        private readonly Config $config,
        private readonly GetSenderCoordinates $getSenderCoordinate
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function execute(
        string $shippingMethodCode,
        LatLngInterface $receiverCoordinate,
        $date,
        ?int $storeId
    ) {
        $senderCoordinate = $this->getSenderCoordinate->execute($storeId);

        $data = $this->getDeliverySlots->execute(
            $senderCoordinate->getLat(),
            $senderCoordinate->getLng(),
            $receiverCoordinate->getLat(),
            $receiverCoordinate->getLng(),
            $date,
            $this->config->getNextDays(),
            $storeId
        );

        $keyFound = array_search($shippingMethodCode, array_column($data, 'type'));

        if ($keyFound === false) {
            return [];
        }

        return $data[$keyFound]['timeSlots'];
    }
}
