<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;

/**
 * Set the trackingCode to the given shipment object.
 * @api
 */
class SetTrackingFromPonyU
{
    public function __construct(
        private readonly ShipmentTrackInterfaceFactory $shipmentTrackFactory
    ) {
    }

    public function execute(ShipmentInterface $shipment, array $ponyUShipmentConfirmation): void
    {
        $shipmentTrack = $this->shipmentTrackFactory->create();

        $shipmentTrack->setTrackNumber($ponyUShipmentConfirmation['trackingCode'] ?? 'no-tracking-code');
        $shipmentTrack->setCarrierCode($shipment->getOrder()->getShippingMethod());
        $shipmentTrack->setOrderId($shipment->getOrderId());

        $shipment->setTracks([$shipmentTrack]);
    }
}
