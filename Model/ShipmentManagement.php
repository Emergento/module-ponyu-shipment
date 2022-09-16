<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model;

use Emergento\PonyU\Model\Client;
use Emergento\PonyUShipment\Api\ShipmentManagementInterface;
use Emergento\PonyUShipment\Exception\NoAvailableTimeSlotException;
use Emergento\PonyUShipment\Exception\OrderIntoThePastException;
use Emergento\PonyUShipment\Model\Builder\ShipmentRequest;
use Emergento\PonyUShipment\Model\Service\GetFirstAvailableSlot;
use Emergento\PonyUShipment\Model\Service\GetNextAvailableDeliverySlotByShipment;
use Emergento\PonyUShippingMethod\Model\Config;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class ShipmentManagement implements ShipmentManagementInterface
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly Client                                 $ponyUClient,
        private readonly ShipmentRequest                        $shipmentRequest,
        private readonly Config                                 $config,
        private readonly GetFirstAvailableSlot                  $getFirstAvailableSlotsByAddress,
        private readonly GetNextAvailableDeliverySlotByShipment $getNextAvailableDeliverySlotByShipment,
        private readonly ValidatePonyUResponse $validatePonyUResponse,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ShipmentInterface $shipment
     * @return array
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function createShipment(ShipmentInterface $shipment): array
    {
        $ponyUResponse = null;
        $attempts = 1;
        while (!$ponyUResponse && $attempts <= self::MAX_ATTEMPTS) {
            try {
                $ponyUResponse = $this->ponyUClient->call('POST', 'v3/secured/shipments', [
                    'json' => $this->shipmentRequest->execute($shipment)
                ], (int) $shipment->getStoreId());
                $shipment->getOrder()->setData('ponyu_slot', json_encode($this->convertConfirmedResponseToDeliverySlot($ponyUResponse)));
            } catch (GuzzleException $exception) {
                if (!$this->config->isNextAvailableSlotEnabled((int) $shipment->getStoreId())) {
                    throw new LocalizedException(__("The slot is not available anymore."));
                }

                try {
                    $this->validatePonyUResponse->execute($exception);
                } catch (OrderIntoThePastException $validatePonyUResponseException) { //
                    $deliverySlot = $this->getFirstAvailableSlotsByAddress->execute($shipment, '');
                    $shipment->getOrder()->setData('ponyu_slot', json_encode($deliverySlot));
                } catch (NoAvailableTimeSlotException $validatePonyUResponseException) {
                    $deliverySlot = $this->getNextAvailableDeliverySlotByShipment->execute($shipment);
                    $shipment->getOrder()->setData('ponyu_slot', json_encode($deliverySlot));
                }
                $this->logger->error($exception->getMessage());
                $attempts++;
            }
        }

        if (!$ponyUResponse) {
            throw new LocalizedException(__('The slot request is not available anymore and no alternative slot is available.'));
        }

        $this->orderRepository->save($shipment->getOrder());

        return $ponyUResponse;
    }

    private function convertConfirmedResponseToDeliverySlot(array $ponyUResponse): array
    {
        return [
            'pickupDate' => $ponyUResponse['confirmedPickupDueDate'],
            'deliveryDateStart' => $ponyUResponse['confirmedRequestedDeliveryRangeStartDate'],
            'deliveryDateEnd' => $ponyUResponse['confirmedRequestedDeliveryRangeEndDate']
        ];
    }

}
