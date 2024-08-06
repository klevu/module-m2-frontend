<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel\Html\Head;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\ViewModel\Html\Head\AddToCart;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Klevu\FrontendApi\Service\Provider\Urls\AddToCartUrlProviderInterface;
use Klevu\FrontendApi\ViewModel\Html\Head\AddToCartInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Url;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Frontend\ViewModel\Html\Head\AddToCart::class
 * @magentoAppArea frontend
 */
class AddToCartTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
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

        $this->implementationFqcn = AddToCart::class;
        $this->interfaceFqcn = AddToCartInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->storeFixturesPool->rollback();
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url http://example.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url https://example.com/
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_link_url http://example.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_link_url https://example.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     */
    public function testGetAddToCartUrl_ReturnsAddToCartUrl(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        /** @var HttpRequest $request */
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        //Emulate HTTPS request
        $request->getServer()->set('HTTPS', 'on');
        $request->getServer()->set('SERVER_PORT', 443);
        $request->setRouteName('catalog');
        $request->setControllerName('category');
        $request->setActionName('index');

        $url = $this->objectManager->create(Url::class, [
            'request' => $request,
        ]);
        $this->objectManager->addSharedInstance(
            instance: $url,
            className: Url::class,
        );

        $addToCartUrlProvider = $this->objectManager->create(AddToCartUrlProviderInterface::class, [
            'request' => $request,
        ]);

        /** @var AddToCartInterface $viewModel */
        $viewModel = $this->instantiateTestObject([
            'addToCartUrlProvider' => $addToCartUrlProvider,
        ]);
        $addToCartUrl = $viewModel->getAddToCartUrl();

        $this->assertStringContainsString(
            needle: 'https://example.com/checkout/cart/add/uenc/',
            haystack: $addToCartUrl,
        );
        $this->assertStringContainsString(
            needle: '?___store=klevu_test_store_1',
            haystack: $addToCartUrl,
        );
    }

    public function testGetFormKey_ReturnsFromKey(): void
    {
        $formKeyObject = $this->objectManager->get(FormKey::class);
        $expectedFormKey = $formKeyObject->getFormKey();

        /** @var AddToCartInterface $viewModel */
        $viewModel = $this->instantiateTestObject();
        $formKey = $viewModel->getFormKey();

        $this->assertSame(expected: $expectedFormKey, actual: $formKey);
    }

    public function testIsEnabled_ReturnsFalse_WhenNotIntegrated(): void
    {
        /** @var AddToCartInterface $viewModel */
        $viewModel = $this->instantiateTestObject();

        $this->assertFalse($viewModel->isEnabled());
    }

    public function testIsEnabled_ReturnsFalse_WhenIntegrated_AllJsv2OutputDisabled(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );
        /** @var AddToCartInterface $viewModel */
        $viewModel = $this->instantiateTestObject();

        $this->assertFalse($viewModel->isEnabled());
    }

    /**
     * @dataProvider dataProvider_invalidIsEnabledConditionType
     */
    public function testIsEnabled_ThrowsException_InDeveloperMode_WhenConfigInvalid(mixed $invalidType): void
    {
        $errorMessage = sprintf(
            'IsEnabledCondition "%s" must be instance of %s;',
            'klevu_integrated',
            IsEnabledConditionInterface::class,
        );

        $this->expectException(InvalidIsEnabledDeterminerException::class);
        $this->expectExceptionMessage($errorMessage);

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->never())
            ->method('error');

        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_DEVELOPER);

        /** @var AddToCartInterface $viewModel */
        $viewModel = $this->instantiateTestObject([
            'appState' => $mockAppState,
            'logger' => $mockLogger,
            'isEnabledConditions' => [
                'klevu_integrated' => $invalidType,
            ],
        ]);
        $viewModel->isEnabled();
    }

    /**
     * @dataProvider dataProvider_invalidIsEnabledConditionType
     */
    public function testIsEnabled_LogsException_InProductionMode_WhenConfigInvalid(mixed $invalidType): void
    {
        $errorMessage = sprintf(
            'IsEnabledCondition "%s" must be instance of %s; %s received',
            'klevu_integrated',
            IsEnabledConditionInterface::class,
            get_debug_type($invalidType),
        );

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error: {error}',
                [
                    'method' => 'Klevu\Frontend\ViewModel\Html\Head\AddToCart::isEnabled',
                    'error' => $errorMessage,
                ],
            );

        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        /** @var AddToCartInterface $viewModel */
        $viewModel = $this->instantiateTestObject([
            'appState' => $mockAppState,
            'logger' => $mockLogger,
            'isEnabledConditions' => [
                'klevu_integrated' => $invalidType,
            ],
        ]);
        $this->assertFalse($viewModel->isEnabled());
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_invalidIsEnabledConditionType(): array
    {
        return [
            [null],
            [false],
            [true],
            [1],
            [1.23],
            ['string'],
            [new DataObject()],
        ];
    }
}
