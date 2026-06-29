<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Model\Checkout;

/**
 * Ensures required checkout jsLayout nodes exist before layout processors run.
 */
class CheckoutJsLayoutNormalizer
{
    public static function normalize(array $jsLayout): array
    {
        if (!isset($jsLayout['components']) || !is_array($jsLayout['components'])) {
            $jsLayout['components'] = [];
        }
        if (!isset($jsLayout['components']['checkout']) || !is_array($jsLayout['components']['checkout'])) {
            $jsLayout['components']['checkout'] = [];
        }
        if (empty($jsLayout['components']['checkout']['component'])) {
            $jsLayout['components']['checkout']['component'] = 'uiComponent';
        }
        if (!isset($jsLayout['components']['checkout']['config']) || !is_array($jsLayout['components']['checkout']['config'])) {
            $jsLayout['components']['checkout']['config'] = [];
        }
        if (empty($jsLayout['components']['checkout']['config']['template'])) {
            $jsLayout['components']['checkout']['config']['template'] = 'Magento_Checkout/onepage';
        }
        if (!isset($jsLayout['components']['checkoutProvider']) || !is_array($jsLayout['components']['checkoutProvider'])) {
            $jsLayout['components']['checkoutProvider'] = [];
        }
        if (empty($jsLayout['components']['checkoutProvider']['component'])) {
            $jsLayout['components']['checkoutProvider']['component'] = 'uiComponent';
        }

        self::ensureRegionComponent(
            $jsLayout,
            ['components', 'checkout', 'children', 'steps'],
            'steps'
        );
        self::ensureRegionComponent(
            $jsLayout,
            ['components', 'checkout', 'children', 'sidebar'],
            'sidebar'
        );
        self::ensureRegionComponent(
            $jsLayout,
            ['components', 'checkout', 'children', 'errors'],
            'messages',
            'Magento_Ui/js/view/messages'
        );
        self::ensureRegionComponent(
            $jsLayout,
            ['components', 'checkout', 'children', 'authentication'],
            'authentication',
            'Magento_Checkout/js/view/authentication'
        );
        self::ensureRegionComponent(
            $jsLayout,
            ['components', 'checkout', 'children', 'progressBar'],
            'progressBar',
            'Magento_Checkout/js/view/progress-bar'
        );

        self::ensurePath(
            $jsLayout,
            ['components', 'checkout', 'children', 'steps', 'children', 'shipping-step'],
            static function (array &$node): void {
                if (empty($node['component'])) {
                    $node['component'] = 'uiComponent';
                }
                if (empty($node['sortOrder'])) {
                    $node['sortOrder'] = '1';
                }
            }
        );

        self::ensurePath(
            $jsLayout,
            ['components', 'checkout', 'children', 'steps', 'children', 'billing-step'],
            static function (array &$node): void {
                if (empty($node['component'])) {
                    $node['component'] = 'uiComponent';
                }
                if (empty($node['sortOrder'])) {
                    $node['sortOrder'] = '2';
                }
            }
        );

        self::ensurePath(
            $jsLayout,
            ['components', 'checkout', 'children', 'steps', 'children', 'shipping-step', 'children', 'shippingAddress'],
            static function (array &$node): void {
                if (empty($node['component'])) {
                    $node['component'] = 'Magento_Checkout/js/view/shipping';
                }
                if (empty($node['provider'])) {
                    $node['provider'] = 'checkoutProvider';
                }
            }
        );

        self::ensurePath(
            $jsLayout,
            ['components', 'checkout', 'children', 'steps', 'children', 'billing-step', 'children', 'payment'],
            static function (array &$node): void {
                if (empty($node['component'])) {
                    $node['component'] = 'Magento_Checkout/js/view/payment';
                }
                if (empty($node['provider'])) {
                    $node['provider'] = 'checkoutProvider';
                }
            }
        );

        self::ensurePath(
            $jsLayout,
            ['components', 'checkout', 'children', 'steps', 'children', 'billing-step', 'children', 'payment', 'children'],
            static function (array &$node): void {
                if (!isset($node['renders']) || !is_array($node['renders'])) {
                    $node['renders'] = ['component' => 'uiComponent', 'children' => []];
                }
                if (!isset($node['renders']['children']) || !is_array($node['renders']['children'])) {
                    $node['renders']['children'] = [];
                }

                if (!isset($node['payments-list']) || !is_array($node['payments-list'])) {
                    $node['payments-list'] = ['component' => 'uiComponent', 'children' => []];
                } elseif (!isset($node['payments-list']['children']) || !is_array($node['payments-list']['children'])) {
                    $node['payments-list']['children'] = [];
                }

                if (!isset($node['afterMethods']) || !is_array($node['afterMethods'])) {
                    $node['afterMethods'] = ['component' => 'uiComponent', 'children' => []];
                } elseif (!isset($node['afterMethods']['children']) || !is_array($node['afterMethods']['children'])) {
                    $node['afterMethods']['children'] = [];
                }
            }
        );

        self::ensurePath(
            $jsLayout,
            ['components', 'checkout', 'children', 'sidebar', 'children', 'summary', 'children', 'totals'],
            static function (array &$node): void {
                if (!isset($node['component'])) {
                    $node['component'] = 'Magento_Checkout/js/view/summary/totals';
                }
                if (!isset($node['displayArea'])) {
                    $node['displayArea'] = 'totals';
                }
                if (!isset($node['children']) || !is_array($node['children'])) {
                    $node['children'] = [];
                }
            }
        );

        self::ensurePath(
            $jsLayout,
            ['components', 'checkout', 'children', 'steps', 'children', 'billing-step', 'children', 'payment', 'children', 'renders', 'children'],
            static function (array &$node): void {
                foreach ($node as $groupCode => &$groupConfig) {
                    if (!is_array($groupConfig)) {
                        $groupConfig = [];
                    }
                    if (!isset($groupConfig['methods']) || !is_array($groupConfig['methods'])) {
                        $groupConfig['methods'] = [];
                    }
                }
            }
        );

        return $jsLayout;
    }

    /**
     * @param string[] $path
     */
    private static function ensurePath(array &$jsLayout, array $path, callable $callback): void
    {
        $node = &$jsLayout;
        foreach ($path as $key) {
            if (!isset($node[$key]) || !is_array($node[$key])) {
                $node[$key] = [];
            }
            $node = &$node[$key];
        }

        $callback($node);
    }

    /**
     * @param string[] $path
     */
    private static function ensureRegionComponent(
        array &$jsLayout,
        array $path,
        string $displayArea,
        string $component = 'uiComponent'
    ): void {
        self::ensurePath($jsLayout, $path, static function (array &$node) use ($displayArea, $component): void {
            if (empty($node['component'])) {
                $node['component'] = $component;
            }
            if (empty($node['displayArea'])) {
                $node['displayArea'] = $displayArea;
            }
        });
    }
}
