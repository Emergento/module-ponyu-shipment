<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Builder;

use Emergento\PonyUShippingMethod\Model\Config as PonyUConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Store\Model\Information;

/**
 * Generate the shipment request.
 * @api
 */
class ShipmentRequest
{
    private ShipmentInterface $shipment;

    public function __construct(
        private readonly SourceRepositoryInterface $sourceRepository,
        private readonly Information $storeInformation,
        private readonly PonyUConfig $ponyUConfig
    ) {
    }

    public function execute(ShipmentInterface $shipment): array
    {
        $this->shipment = $shipment;

        return [
            'orderId' => $shipment->getOrder()->getIncrementId(),
            'order' => $this->getOrderInfo(),
            'senderInfo' => $this->getSenderInfo(),
            'customerInfo' => $this->getCustomerInfo(),
            'paymentInfo' => $this->getPaymentInfo()
        ];
    }

    private function getOrderInfo(): array
    {
        $ponyUSlot = json_decode($this->shipment->getOrder()->getPonyuSlot(), true);

        return [
            'pickupDueDate' => $ponyUSlot['pickupDate'],
            'requestedDeliveryRangeStartDate' => $ponyUSlot['deliveryDateStart'],
            'requestedDeliveryRangeEndDate' =>  $ponyUSlot['deliveryDateEnd'],
            'promptAsap' => $ponyUSlot['instant'] ?? false
        ];
    }

    /**
     * Return inventory information associated with the shipment, otherwise return the store address.
     * @return array
     */
    private function getSenderInfo(): array
    {
        $storeData = $this->storeInformation->getStoreInformationObject($this->shipment->getStore());
        $sourceInventoryCode = $this->shipment->getExtensionAttributes()->getSourceCode();
        try {
            $sourceInventory = $this->sourceRepository->get($sourceInventoryCode);
            return [
                'name' => $storeData->getData('name') ?? '',
                'phoneNumber' => $sourceInventory->getPhone() ?? $storeData->getData('phone') ?? '',
                'email' => $sourceInventory->getEmail() ?? $this->ponyUConfig->getStoreSupportEmail((int) $this->shipment->getStoreId()),
                'address' => $sourceInventory->getStreet() ?? $storeData->getData('street_line1') ?? '',
                'city' => $sourceInventory->getCity() ?? $storeData->getData('city') ?? '',
                'country' => $sourceInventory->getCountryId() ?? $storeData->getData('country_id') ?? '',
                'postcode' => $sourceInventory->getPostcode() ?? $storeData->getData('postcode') ?? '',
                'addressInfo' => ''
            ];

        } catch (NoSuchEntityException $e) {
            return [
                'name' => $storeData->getData('name') ?? '',
                'phoneNumber' => $storeData->getData('phone') ?? '',
                'email' => $this->ponyUConfig->getStoreSupportEmail((int) $this->shipment->getStoreId()),
                'address' => $storeData->getData('street_line1') ?? '',
                'city' => $storeData->getData('city') ?? '',
                'country' => $storeData->getData('country_id') ?? '',
                'postcode' => $storeData->getData('postcode') ?? '',
                'addressInfo' => ''
            ];
        }

    }

    private function getCustomerInfo(): array
    {
        $shippingAddress = $this->shipment->getShippingAddress();
        return [
            'name' => $shippingAddress->getName(),
            'phoneNumber' => $shippingAddress->getTelephone(),
            'email' => $shippingAddress->getEmail(),
            'address' =>$shippingAddress->getStreetLine(1),
            'city' => $shippingAddress->getCity(),
            'postcode' => $shippingAddress->getPostcode(),
            'addressInfo' => $shippingAddress->getStreetLine(2)
        ];
    }

    private function getPaymentInfo(): array
    {
        return [
            'cashOnDelivery' => false,
            'deliveryCharge' => 0,
            'total' => $this->shipment->getOrder()->getGrandTotal()
        ];
    }

}
