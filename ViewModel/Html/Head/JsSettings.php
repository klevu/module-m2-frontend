<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel\Html\Head;

use Klevu\FrontendApi\Service\KlevuSettingsBuilderInterface;
use Klevu\FrontendApi\ViewModel\Html\Head\JsSettingsInterface;
use Psr\Log\LoggerInterface;

class JsSettings implements JsSettingsInterface
{
    /**
     * @var KlevuSettingsBuilderInterface
     */
    private readonly KlevuSettingsBuilderInterface $klevuSettingsBuilder;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var string|null
     */
    private ?string $klevuSettings = null;

    /**
     * @param KlevuSettingsBuilderInterface $klevuSettingsBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        KlevuSettingsBuilderInterface $klevuSettingsBuilder,
        LoggerInterface $logger,
    ) {
        $this->klevuSettingsBuilder = $klevuSettingsBuilder;
        $this->logger = $logger;
    }

    /**
     * @return string|null
     */
    public function getKlevuJsSettings(): ?string
    {
        if (null === $this->klevuSettings) {
            try {
                $return = $this->klevuSettingsBuilder->execute();
            } catch (\Exception $exception) {
                $return = "{error: 'An error occurred while building Klevu Settings. See log for details.'}";
                $this->logger->error(
                    message: 'Method: {method}, Error: {error}',
                    context: [
                        'method' => __METHOD__,
                        'error' => $exception->getMessage(),
                    ],
                );
            }
            $this->klevuSettings = $return;
        }

        return $this->klevuSettings;
    }

    /**
     * @return bool
     */
    public function isSettingsGenerationError(): bool
    {
        $klevuSettings = $this->getKlevuJsSettings();

        return str_starts_with(
            haystack: $klevuSettings,
            needle: "{error: '",
        );
    }
}
