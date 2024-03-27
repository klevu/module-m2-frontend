<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Layout;

use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\InvalidRelLinkProviderConfigurationException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\Provider\Layout\RelLinkProviderInterface;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\FrontendApi\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class RelLinkProvider implements RelLinkProviderInterface
{
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $validator;
    /**
     * @var IsEnabledDeterminerInterface
     */
    private readonly IsEnabledDeterminerInterface $isEnabledDeterminer;
    /**
     * @var mixed[][]
     */
    private readonly array $links;

    /**
     * @param LoggerInterface $logger
     * @param ValidatorInterface $validator
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[][] $links
     */
    public function __construct(
        LoggerInterface $logger,
        ValidatorInterface $validator,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $links,
    ) {
        $this->logger = $logger;
        $this->validator = $validator;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->links = $links;
    }

    /**
     * @return string[][]
     */
    public function get(): array
    {
        $return = [];
        foreach ($this->links as $link) {
            try {
                $this->validateLinkData($link);
                $linkData = [];
                $linkData[self::RESOURCE_PATH] = $this->getLinkPath($link[self::RESOURCE_PATH]);
                $linkData[self::RESOURCE_TYPE] = $link[self::RESOURCE_TYPE];
                $return[] = $linkData;
            } catch (OutputDisabledException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // this is fine
                // do not add this link and move onto the next one
            } catch (InvalidRelLinkProviderConfigurationException | InvalidIsEnabledDeterminerException $exception) {
                $this->logger->error(
                    message: 'Method: {method}, Error: {error}',
                    context: [
                        'method' => __METHOD__,
                        'error' => $exception->getMessage(),
                    ],
                );
            }
        }

        return array_filter(
            array: $return,
            callback: static fn ($link) => (null !== $link[self::RESOURCE_PATH]),
        );
    }

    /**
     * @param mixed $link
     *
     * @return bool|int|string|null
     * @throws OutputDisabledException
     */
    private function getLinkPath(mixed $link): string|int|bool|null
    {
        return match (true) {
            ($link instanceof SettingsProviderInterface) => $link->get(),
            (is_string($link)) => $link,
            default => null,
        };
    }

    /**
     * @param mixed[] $link
     *
     * @return void
     * @throws InvalidRelLinkProviderConfigurationException
     * @throws OutputDisabledException
     * @throws InvalidIsEnabledDeterminerException
     */
    private function validateLinkData(array $link): void
    {
        if ($this->validator->isValid($link)) {
            $this->isEnabledDeterminer->executeAnd($link['is_enabled_conditions'] ?? []);
            return;
        }
        $messages = $this->validator->getMessages();
        throw new InvalidRelLinkProviderConfigurationException(
            __(
                'Invalid Rel Links: %1',
                implode(', ', $messages),
            ),
        );
    }
}
