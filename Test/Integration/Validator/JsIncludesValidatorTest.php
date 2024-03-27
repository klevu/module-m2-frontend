<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Validator;

use Klevu\Frontend\Validator\JsIncludesValidator;
use Klevu\Frontend\ViewModel\Html\Head\JsIncludes;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\FrontendApi\Validator\ValidatorInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class JsIncludesValidatorTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var Json|null
     */
    private ?Json $serializer = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = JsIncludesValidator::class;
        $this->interfaceFqcn = ValidatorInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->serializer = $this->objectManager->get(Json::class);
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
                'Expected array, received %s (%s).',
                get_debug_type($data),
                $this->serializer->serialize($data),
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
     * @dataProvider dataProvider_testIsValid_ReturnsFalse_ResourcePathIsMissingOrNotString
     */
    public function testIsValid_ReturnsFalse_ResourcePathIsMissingOrNotString(mixed $data): void
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->instantiateTestObject();
        $value = [
            JsIncludes::RESOURCE_PATH => $data,
        ];
        $this->assertFalse(
            condition: $validator->isValid($value),
        );
        $this->assertContains(
            needle: sprintf(
                'Link %s must be a none empty string. Received %s (%s).',
                JsIncludes::RESOURCE_PATH,
                get_debug_type($data),
                $this->serializer->serialize($value),
            ),
            haystack: $validator->getMessages(),
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testIsValid_ReturnsFalse_ResourcePathIsMissingOrNotString(): array
    {
        return [
            [null],
            [false],
            [true],
            [0],
            [1],
            [12.34],
            [['string']],
            [new DataObject()],
            [''],
            ['   '],
        ];
    }

    /**
     * @dataProvider dataProvider_testIsValid_ReturnsFalse_ResourceProviderNotSetAndPathNotHttps
     */
    public function testIsValid_ReturnsFalse_ResourceProviderNotSetAndPathNotHttps(mixed $path): void
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->instantiateTestObject();
        $value = [
            JsIncludes::RESOURCE_PATH => $path,
            JsIncludes::RESOURCE_PROVIDER => null,
        ];
        $this->assertFalse(
            condition: $validator->isValid($value),
        );
        $this->assertContains(
            needle: sprintf(
                'Either Link %s must begin with "%s" or %s must be set. Received %s.',
                JsIncludes::RESOURCE_PATH,
                'https://',
                JsIncludes::RESOURCE_PROVIDER,
                $this->serializer->serialize($value),
            ),
            haystack: $validator->getMessages(),
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testIsValid_ReturnsFalse_ResourceProviderNotSetAndPathNotHttps(): array
    {
        return [
            ['test.com'],
            ['http://klevu.com'],
            ['https:/klevu.com'],
        ];
    }

    /**
     * @dataProvider dataProvider_testIsValid_ReturnsFalse_ResourceProviderInvalidType
     */
    public function testIsValid_ReturnsFalse_ResourceProviderInvalidType(mixed $provider): void
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->instantiateTestObject();
        $value = [
            JsIncludes::RESOURCE_PATH => 'some/path',
            JsIncludes::RESOURCE_PROVIDER => $provider,
        ];
        $this->assertFalse(
            condition: $validator->isValid($value),
        );
        $this->assertContains(
            needle: sprintf(
                'Either Link %s must be string or instance of %s. Received %s (%s).',
                JsIncludes::RESOURCE_PROVIDER,
                SettingsProviderInterface::class,
                get_debug_type($value[JsIncludes::RESOURCE_PROVIDER]),
                $this->serializer->serialize($value),
            ),
            haystack: $validator->getMessages(),
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testIsValid_ReturnsFalse_ResourceProviderInvalidType(): array
    {
        return [
            [true],
            [0],
            [1],
            [12.34],
            [''],
            ['   '],
            [['string']],
            [new DataObject()],
        ];
    }
}
