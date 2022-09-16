<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Service;

use Magento\Sales\Api\Data\ShipmentCommentCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment\NotifierInterface;

/**
 * Send an email with shipping information to the customer, including the delivery time.
 * @api
 */
class NotifyShipmentToCustomer
{
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly ShipmentCommentCreationInterfaceFactory $shipmentCommentCreationFactory,
        private readonly GenerateDeliveryInformationLabel $generateDeliveryInformationLabel,
    ) {

    }

    public function execute(ShipmentInterface $shipment): void
    {
        $shipmentCommentCreation = $this->shipmentCommentCreationFactory->create();
        $shipmentCommentCreation->setComment((string) $this->generateDeliveryInformationLabel->execute($shipment->getOrder()));
        $shipmentCommentCreation->setIsVisibleOnFront(1);
        $this->notifier->notify($shipment->getOrder(), $shipment, $shipmentCommentCreation);
    }
}
