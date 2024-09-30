<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Setup\Patch\Data;

use Klevu\Configuration\Setup\Traits\MigrateLegacyConfigurationSettingsTrait;
use Klevu\Frontend\Constants;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateLegacyConfigurationSettings implements DataPatchInterface
{
    use MigrateLegacyConfigurationSettingsTrait;

    public const XML_PATH_LEGACY_CUSTOMER_GROUP_PRICING = 'klevu_search/price_per_customer_group/enabled';

    /**
     * @param WriterInterface $configWriter
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        WriterInterface $configWriter,
        ResourceConnection $resourceConnection,
    ) {
        $this->configWriter = $configWriter;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return $this
     */
    public function apply(): self
    {
        $this->migrateCustomerGroupPricing();

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return void
     */
    private function migrateCustomerGroupPricing(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_CUSTOMER_GROUP_PRICING,
            toPath: Constants::XML_PATH_CUSTOMER_GROUP_PRICING,
        );
    }
}
