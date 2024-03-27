<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Controller\Adminhtml\System\Config;

use Klevu\Configuration\Test\Integration\Controller\Adminhtml\GetAdminFrontNameTrait;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\TestFramework\Response as TestFrameworkResponse;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

class CustomSettingsDynamicRowsRenderTest extends AbstractBackendControllerTestCase
{
    use GetAdminFrontNameTrait;

    /**
     * @return void
     * @throws AuthenticationException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = $this->getAdminFrontName() . '/admin/system_config/edit';
        $this->resource = 'Klevu_Configuration::developer';
        $this->expectedNoAccessResponseCode = 302;
        /** @var Request $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_developer');
    }

    public function testDynamicRows_IsDisplayed(): void
    {
        $this->resource = 'Klevu_FrontendApi::developer_frontend';

        $this->dispatch($this->uri);

        /** @var TestFrameworkResponse $response */
        $response = $this->getResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $responseBody = $response->getBody();

        $matches = [];
        preg_match(
            pattern: '#<tr id="row_klevu_developer_frontend_klevu_settings">' .
            '<td class="label"><label for="klevu_developer_frontend_klevu_settings">' .
            '<span data-config-scope="[STORE VIEW]">Custom JSv2 Settings</span>' .
            '</label></td>#',
            subject: $responseBody,
            matches: $matches,
        );
        $this->assertCount(
            expectedCount: 0,
            haystack: $matches,
            message: 'Dynamic rows label rendered',
        );

        $matches = [];
        preg_match(
            pattern: '#<table class="admin__control-table" id="klevu_developer_frontend_klevu_settings">' .
            '<thead><tr><th>Path</th><th>Type</th><th>Value</th><th class="col-actions" colspan="1">Action</th>' .
            '</tr></thead>#',
            subject: $responseBody,
            matches: $matches,
        );
        $this->assertCount(
            expectedCount: 0,
            haystack: $matches,
            message: 'Dynamic rows table rendered',
        );
    }
}
