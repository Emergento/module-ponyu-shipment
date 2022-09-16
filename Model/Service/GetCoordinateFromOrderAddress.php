<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Service;

use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;

/**
 * Get latitude & longitude from order address
 */
class GetCoordinateFromOrderAddress
{
    public function __construct(
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly GetLatLngFromAddressInterface $getLatLngFromAddress
    ) {
    }

    public function execute(OrderAddressInterface $address): LatLngInterface
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
