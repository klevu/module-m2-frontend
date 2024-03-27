<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Unit\Service\Provider\Sdk\UserAgent\SystemInformation;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Composer\InstalledVersions;
use Klevu\Frontend\Service\Provider\Sdk\UserAgent\SystemInformation\FrontendProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use PHPUnit\Framework\TestCase;

class FrontendProviderTest extends TestCase
{
    public function testIsInstanceOfInterface(): void
    {
        $frontendProvider = new FrontendProvider();

        $this->assertInstanceOf(
            expected: UserAgentProviderInterface::class,
            actual: $frontendProvider,
        );
    }

    public function testExecute_ComposerInstall(): void
    {
        if (!InstalledVersions::isInstalled('klevu/module-m2-frontend')) {
            $this->markTestSkipped('Module not installed by composer');
        }

        $frontendProvider = new FrontendProvider();

        $result = $frontendProvider->execute();

        $this->assertStringContainsString(
            needle: 'klevu-m2-frontend/' . $this->getLibraryVersion(),
            haystack: $result,
        );
    }

    public function testExecute_AppInstall(): void
    {
        if (InstalledVersions::isInstalled('klevu/module-m2-frontend')) {
            $this->markTestSkipped('Module installed by composer');
        }

        $frontendProvider = new FrontendProvider();

        $result = $frontendProvider->execute();

        $this->assertSame(
            expected: 'klevu-m2-frontend',
            actual: $result,
        );
    }

    /**
     * @return string
     */
    private function getLibraryVersion(): string
    {
        $composerFilename = __DIR__ . '/../../../../../../../composer.json';
        $composerContent = json_decode(
            json: file_get_contents($composerFilename) ?: '{}',
            associative: true,
        );
        if (!is_array($composerContent)) {
            $composerContent = [];
        }

        $version = $composerContent['version'] ?? '-';
        $versionParts = explode('.', $version) + array_fill(0, 4, '0');

        return implode('.', $versionParts);
    }
}
