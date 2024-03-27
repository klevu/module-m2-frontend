<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Validator;

use Klevu\Frontend\Service\Provider\ScopeConfigSettingsProvider;
use Klevu\Frontend\Validator\RelLinkValidator;
use Klevu\FrontendApi\Service\Provider\Layout\RelLinkProviderInterface;
use Klevu\FrontendApi\Validator\ValidatorInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class RelLinkValidatorTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = RelLinkValidator::class;
        $this->interfaceFqcn = ValidatorInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @dataProvider dataProvider_testIsValid_ReturnsFalse_InvalidDataType
     */
    public function testIsValid_ReturnsFalse_InvalidDataType(mixed $data): void
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->instantiateTestObject();
        $this->assertFalse(condition: $validator->isValid($data));
        $this->assertContains(
            needle: sprintf(
                'Invalid Data provided for rel link configuration. Expected array, received %s.',
                get_debug_type($data),
            ),
            haystack: $validator->getMessages(),
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testIsValid_ReturnsFalse_InvalidDataType(): array
    {
        return [
            [null],
            [true],
            [false],
            [1],
            [1.23],
            ['string'],
            [new DataObject()],
        ];
    }

    /**
     * @dataProvider dataProvider_testIsValid_ReturnsFalse_InvalidResourcePath
     */
    public function testIsValid_ReturnsFalse_InvalidResourcePath(mixed $data): void
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->instantiateTestObject();
        $this->assertFalse(
            condition: $validator->isValid([
                RelLinkProviderInterface::RESOURCE_TYPE => 'string',
                RelLinkProviderInterface::RESOURCE_PATH => $data,
            ]),
        );
        $this->assertContains(
            needle: sprintf(
                'Invalid Resource Path provided for rel link. ' .
                'Expected string or instance of SettingsProviderInterface, received %s.',
                get_debug_type($data),
            ),
            haystack: $validator->getMessages(),
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testIsValid_ReturnsFalse_InvalidResourcePath(): array
    {
        return [
            [null],
            [true],
            [false],
            [1],
            [1.23],
            [new DataObject()],
            [[1]],
        ];
    }

    /**
     * @dataProvider dataProvider_testIsValid_ReturnsFalse_InvalidResourceType
     */
    public function testIsValid_ReturnsFalse_InvalidResourceType(mixed $data): void
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->instantiateTestObject();
        $this->assertFalse(
            condition: $validator->isValid([
                RelLinkProviderInterface::RESOURCE_TYPE => $data,
                RelLinkProviderInterface::RESOURCE_PATH => 'string',
            ]),
        );
        $this->assertContains(
            needle: sprintf(
                'Invalid Resource Type provided for rel link. Expected string, received %s.',
                get_debug_type($data),
            ),
            haystack: $validator->getMessages(),
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testIsValid_ReturnsFalse_InvalidResourceType(): array
    {
        return [
            [null],
            [true],
            [false],
            [1],
            [1.23],
            [new DataObject()],
            [[1]],
        ];
    }

    /**
     * @dataProvider dataProvider_testIsValid_ReturnsTrue_ValidData
     */
    public function testIsValid_ReturnsTrue_ValidData(mixed $data): void
    {
        if (str_contains(haystack: $data, needle: '::class')) {
            $data = $this->objectManager->get($data);
        }
        /** @var ValidatorInterface $validator */
        $validator = $this->instantiateTestObject();
        $this->assertTrue(
            condition: $validator->isValid([
                RelLinkProviderInterface::RESOURCE_TYPE => 'string',
                RelLinkProviderInterface::RESOURCE_PATH => $data,
            ]),
        );
        $this->assertCount(expectedCount: 0, haystack: $validator->getMessages());
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testIsValid_ReturnsTrue_ValidData(): array
    {
        return [
            ['string'],
            [ScopeConfigSettingsProvider::class],
        ];
    }
}
