<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Service;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\ShipmentInterface;
use Emergento\PonyUShippingMethod\Model\GetNextAvailableDeliverySlot;

/**
 * Get next available delivery slot to the selected slot
 */
class GetNextAvailableDeliverySlotByShipment
{

    public function __construct(
        private readonly GetNextAvailableDeliverySlot $getNextAvailableDeliverySlot,
        private readonly GetCoordinateFromOrderAddress $getCoordinateFromOrderAddress,
        private readonly Json $json
    ) {
    }

    /**
     * @throws GuzzleException|LocalizedException
     */
    public function execute(ShipmentInterface $shipment)
    {
        return $this->getNextAvailableDeliverySlot->execute(
            str_replace('ponyu_', '', $shipment->getOrder()->getShippingMethod()),
            $this->getCoordinateFromOrderAddress->execute($shipment->getShippingAddress()),
            (int) $shipment->getStoreId(),
            $this->json->unserialize($shipment->getOrder()->getPonyuSlot())
        );
    }
}
