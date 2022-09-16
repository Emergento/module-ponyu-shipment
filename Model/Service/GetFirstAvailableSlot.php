<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Service;

use GuzzleHttp\Exception\GuzzleException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Retrieve first available slot for a given shipping method
 * @api
 */
class GetFirstAvailableSlot
{
    public function __construct(
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly GetLatLngFromAddressInterface $getLatLngFromAddress,
        private readonly GetSlotsByShippingMethodCode $getSlotsByShippingMethodCode
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function execute(ShipmentInterface $shipment, $date)
    {
        return $this->getSlotsByShippingMethodCode->execute(
            str_replace('ponyu_', '', $shipment->getOrder()->getShippingMethod()),
            $this->getReceiverCoordinate($shipment->getShippingAddress()),
            $date,
            (int) $shipment->getStoreId()
        )[0];
    }

    private function getReceiverCoordinate(OrderAddressInterface $address): LatLngInterface
    {
        $addressToInventorySourceSelectionAddress = $this->addressFactory->create([
            'street' => $address->getStreetLine(1) ?? '',
            'country' => $address->getCountryId() ?? '',
            'city' => $address->getCity() ?? '',
            'region' => $address->getRegion() ?? '',
            'postcode' => $address->getPostcode() ?? '',
        ]);

        return $this->getLatLngFromAddress->execute($addressToInventorySourceSelectionAddress);
    }
}
