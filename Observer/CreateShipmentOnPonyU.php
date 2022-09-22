<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Observer;

use Emergento\PonyUShipment\Api\ShipmentManagementInterface;
use Emergento\PonyUShipment\Model\SetTrackingFromPonyU;
use Emergento\PonyUShipment\Model\Service\IsPonyUOrder;
use Emergento\PonyUShipment\Model\Service\NotifyShipmentToCustomer;
use Emergento\PonyUShippingMethod\Model\Config as PonyUShippingMethodConfig;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentCommentInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Create a shipment on PonyU
 * Save the necessary information on the shipment object.
 * Notify to the customer of the shipment, including the delivery information.
 * @api
 */
class CreateShipmentOnPonyU implements ObserverInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param LoggerInterface $logger
     * @param SetTrackingFromPonyU $setTrackingFromPonyU
     * @param ShipmentManagementInterface $ponyUShipmentManagement
     * @param IsPonyUOrder $isPonyUOrder
     * @param NotifyShipmentToCustomer $notifyShipmentToCustomer
     * @param ShipmentCommentInterfaceFactory $shipmentCommentFactory
     */
    public function __construct(
        private readonly OrderRepositoryInterface        $orderRepository,
        private readonly ShipmentRepositoryInterface     $shipmentRepository,
        private readonly LoggerInterface                 $logger,
        private readonly SetTrackingFromPonyU            $setTrackingFromPonyU,
        private readonly ShipmentManagementInterface     $ponyUShipmentManagement,
        private readonly IsPonyUOrder                    $isPonyUOrder,
        private readonly NotifyShipmentToCustomer        $notifyShipmentToCustomer,
        private readonly ShipmentCommentInterfaceFactory $shipmentCommentFactory
    ) {
    }

    /**
     * Create shipment
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var ShipmentInterface $shipment */
        $shipment = $observer->getData('shipment');

        if (!$this->isPonyUOrder->execute($shipment->getOrder())) {
            return;
        }
        try {
            $ponyUResponse = $this->ponyUShipmentManagement->createShipment($shipment);
            $this->setTrackingFromPonyU->execute($shipment, $ponyUResponse);
            $this->setDeliveryCommentsOnShipment($shipment, $ponyUResponse);
            $this->shipmentRepository->save($shipment);
            $this->notifyShipmentToCustomer->execute($shipment);
        } catch (\Exception | LocalizedException | GuzzleException $e) {
            $shipment->getOrder()->setData('ponyu_shipment_creation_last_error', $e->getMessage());
            $this->orderRepository->save($shipment->getOrder());
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Set comments on shipment
     *
     * @param ShipmentInterface $shipment
     * @param array $ponyUShipmentConfirmation
     * @return void
     * @throws \Exception
     */
    private function setDeliveryCommentsOnShipment(ShipmentInterface $shipment, array $ponyUShipmentConfirmation): void
    {
        $pickupComment = $this->shipmentCommentFactory->create();
        $deliveryComment = $this->shipmentCommentFactory->create();

        $confirmedPickupDueDate = new \DateTime(
            $ponyUShipmentConfirmation['confirmedPickupDueDate'],
            new \DateTimeZone(PonyUShippingMethodConfig::PONYU_TIMEZONE)
        );
        $confirmedDeliveryStartDate = new \DateTime(
            $ponyUShipmentConfirmation['confirmedRequestedDeliveryRangeStartDate'],
            new \DateTimeZone(PonyUShippingMethodConfig::PONYU_TIMEZONE)
        );
        $confirmedDeliveryEndDate = new \DateTime(
            $ponyUShipmentConfirmation['confirmedRequestedDeliveryRangeEndDate'],
            new \DateTimeZone(PonyUShippingMethodConfig::PONYU_TIMEZONE)
        );

        $pickupComment->setComment(__(
            'Package will be picked up from %1 on %2',
            $confirmedPickupDueDate->format('H:i'),
            $confirmedPickupDueDate->format('d/m/Y')
        ));
        $deliveryComment->setComment(__(
            'Package will be delivered from %1 to %2 on %3',
            $confirmedDeliveryStartDate->format('H:i'),
            $confirmedDeliveryEndDate->format('H:i'),
            $confirmedDeliveryStartDate->format('d/m/Y')
        ));

        $shipment->setComments([$pickupComment, $deliveryComment]);
    }
}
