<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model;

use GuzzleHttp\Exception\GuzzleException;
use Emergento\PonyUShipment\Model\Service\GetSlotsByShippingMethodCode;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magento\Store\Model\StoreManagerInterface;


/**
 * Check if a slot is still valid for a delivery.
 * @api
 */
class IsDeliverySlotValid
{
    public function __construct(
        private readonly GetSlotsByShippingMethodCode $getSlotsByShippingMethodCode
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function execute(string $methodCode, LatLngInterface $shippingDestinationCoordinates, $slotToCheck, int $storeId): bool
    {
        $slotsForMethodCode = $this->getSlotsByShippingMethodCode->execute(
            $methodCode,
            $shippingDestinationCoordinates,
            '',
            $storeId
        );
        foreach ($slotsForMethodCode as $slot) {
            if ($slot['pickupDate'] === $slotToCheck['pickupDate']) {
                return true;
            }
        }

        return false;
    }
}
