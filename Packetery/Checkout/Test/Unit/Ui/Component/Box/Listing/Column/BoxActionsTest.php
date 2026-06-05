<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Ui\Component\Box\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Packetery\Checkout\Model\Box;
use Packetery\Checkout\Test\BaseTest;
use Packetery\Checkout\Ui\Component\Box\Listing\Column\Actions;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BoxActionsTest extends BaseTest
{
    private const BOX_ID = 10;

    /**
     * A non-default box must offer both "Set as default" and "Delete" actions
     */
    public function testNonDefaultBoxOffersBothVariants(): void
    {
        $urlBuilder = $this->createMockWithProps(UrlInterface::class);
        $urlBuilder->method('getUrl')
            ->willReturnMap(
                [
                    [Actions::URL_PATH_EDIT, [Box::ID => self::BOX_ID], 'packetery/box/detail/id/' . self::BOX_ID],
                    [Actions::URL_PATH_SET_DEFAULT, [Box::ID => self::BOX_ID], 'packetery/box/setAsDefault/id/' . self::BOX_ID],
                    [Actions::URL_PATH_DELETE, [Box::ID => self::BOX_ID], 'packetery/box/delete/id/' . self::BOX_ID],
                ]
            );

        $component = new Actions(
            $this->createMockWithProps(ContextInterface::class),
            $this->createMockWithProps(UiComponentFactory::class),
            $urlBuilder,
            [],
            ['name' => 'actions']
        );

        $result = $component->prepareDataSource(
            [
                'data' => [
                    'items' => [
                        [
                            Box::ID => self::BOX_ID,
                            Box::IS_DEFAULT => 0,
                            'name' => 'M',
                        ],
                    ],
                ],
            ]
        );

        $this->assertArrayHasKey('setDefault', $result['data']['items'][0]['actions']);
        $this->assertArrayHasKey('delete', $result['data']['items'][0]['actions']);
        $this->assertArrayHasKey('edit', $result['data']['items'][0]['actions']);
    }

    /**
     * A default box must not offer "Set as default" or "Delete"
     */
    public function testDefaultBoxHidesVariants(): void
    {
        $urlBuilder = $this->createMockWithProps(UrlInterface::class);
        $urlBuilder->method('getUrl')
            ->willReturnMap(
                [
                    [
                        Actions::URL_PATH_EDIT,
                        [Box::ID => self::BOX_ID],
                        'packetery/box/detail/id/' . self::BOX_ID
                    ],
                ]
            );

        $component = new Actions(
            $this->createMockWithProps(ContextInterface::class),
            $this->createMockWithProps(UiComponentFactory::class),
            $urlBuilder,
            [],
            ['name' => 'actions']
        );

        $result = $component->prepareDataSource(
            [
                'data' => [
                    'items' => [
                        [
                            Box::ID => self::BOX_ID,
                            Box::IS_DEFAULT => 1,
                            'name' => 'L',
                        ],
                    ],
                ],
            ]
        );

        $this->assertArrayHasKey('edit', $result['data']['items'][0]['actions']);
        $this->assertArrayNotHasKey('setDefault', $result['data']['items'][0]['actions']);
        $this->assertArrayNotHasKey('delete', $result['data']['items'][0]['actions']);
    }
}
