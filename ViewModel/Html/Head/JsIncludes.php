<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel\Html\Head;

use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\InvalidJsIncludeConfigurationException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\FrontendApi\Validator\ValidatorInterface;
use Klevu\FrontendApi\ViewModel\JsIncludesInterface;
use Magento\Framework\App\State as AppState;
use Psr\Log\LoggerInterface;

class JsIncludes implements JsIncludesInterface
{
    public const RESOURCE_PROVIDER = 'provider';
    public const RESOURCE_PATH = 'path';

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $validator;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var IsEnabledDeterminerInterface
     */
    private readonly IsEnabledDeterminerInterface $isEnabledDeterminer;
    /**
     * @var mixed[][]
     */
    private readonly array $jsIncludes;

    /**
     * @param LoggerInterface $logger
     * @param ValidatorInterface $validator
     * @param AppState $appState
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[][] $jsIncludes
     */
    public function __construct(
        LoggerInterface $logger,
        ValidatorInterface $validator,
        AppState $appState,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $jsIncludes = [],
    ) {
        $this->logger = $logger;
        $this->validator = $validator;
        $this->appState = $appState;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->jsIncludes = $jsIncludes;
    }

    /**
     * @return string[]
     * @throws InvalidJsIncludeConfigurationException
     */
    public function getLinks(): array
    {
        $return = [];

        foreach ($this->jsIncludes as $key => $link) {
            try {
                $return[$key] = $this->generateUrl($link);
            } catch (InvalidJsIncludeConfigurationException | InvalidIsEnabledDeterminerException $exception) {
                if ($this->appState->getMode() !== AppState::MODE_PRODUCTION) {
                    throw $exception;
                }
                $this->logger->error(
                    message: 'Method: {method}, Error: {error}',
                    context: [
                        'method' => __METHOD__,
                        'error' => $exception->getMessage(),
                    ],
                );
            } catch (OutputDisabledException) { //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // Output of link is disabled.
                // This is fine, move onto next link.
            }
        }

        // @TODO order the array

        return $return;
    }

    /**
     * @param mixed[] $link
     *
     * @return string
     * @throws OutputDisabledException
     * @throws InvalidJsIncludeConfigurationException
     * @throws InvalidIsEnabledDeterminerException
     */
    private function generateUrl(array $link): string
    {
        $this->validateLink($link);
        $baseUrl = $this->getBaseUrl($link[self::RESOURCE_PROVIDER] ?? null);

        return $baseUrl . ltrim(string: $link[self::RESOURCE_PATH], characters: ' /');
    }

    /**
     * @param mixed[] $link
     *
     * @return void
     * @throws InvalidJsIncludeConfigurationException
     * @throws OutputDisabledException
     * @throws InvalidIsEnabledDeterminerException
     */
    private function validateLink(array $link): void
    {
        if ($this->validator->isValid($link)) {
            $this->isEnabledDeterminer->executeAnd(isEnabledConditions: $link['is_enabled_conditions'] ?? []);

            return;
        }
        $messages = $this->validator->hasMessages()
            ? $this->validator->getMessages()
            : [];
        throw new InvalidJsIncludeConfigurationException(
            __(
                'Invalid Data provided for JsIncludes configuration: %1',
                implode('; ', $messages),
            ),
        );
    }

    /**
     * @param mixed $link
     *
     * @return string
     * @throws OutputDisabledException
     */
    private function getBaseUrl(mixed $link): string
    {
        $baseUrl = match (true) {
            (($link ?? null) instanceof SettingsProviderInterface) => $this->getLinkFromInstance($link),
            (is_string($link)) => trim($link),
            default => null,
        };
        if ($baseUrl) {
            $baseUrl = rtrim(string: $baseUrl, characters: '/') . '/';
        }

        return $baseUrl ?? '';
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     *
     * @return string
     * @throws OutputDisabledException
     */
    private function getLinkFromInstance(SettingsProviderInterface $settingsProvider): string
    {
        $link = trim($settingsProvider->get());

        return str_starts_with(haystack: $link, needle: 'https://')
            ? $link
            : 'https://' . $link;
    }
}
