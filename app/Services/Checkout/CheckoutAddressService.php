<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Dao\UserDao;
use App\Models\User;
use Core\Validator;

/**
 * Normalisation / validation des adresses checkout (sans HTTP).
 */
final class CheckoutAddressService
{
    public function __construct(private UserDao $userDao) {}

    /**
     * @param array<string, mixed> $input
     * @return array{
     *   shipping: array{
     *     shipping_name: string,
     *     shipping_line1: string,
     *     shipping_postal: string,
     *     shipping_city: string,
     *     shipping_country: string
     *   },
     *   billing: array{
     *     billing_name: string,
     *     billing_line1: string,
     *     billing_postal: string,
     *     billing_city: string,
     *     billing_country: string,
     *     billing_same_as_shipping: string
     *   },
     *   billing_same: bool,
     *   errors: array<string, list<string>>
     * }
     */
    public function parseAndValidateFromInput(array $input, bool $billingSame): array
    {
        $shipping = [
            'shipping_name'    => trim((string) ($input['shipping_name'] ?? '')),
            'shipping_line1'   => trim((string) ($input['shipping_line1'] ?? '')),
            'shipping_postal'    => trim((string) ($input['shipping_postal'] ?? '')),
            'shipping_city'    => trim((string) ($input['shipping_city'] ?? '')),
            'shipping_country' => strtoupper(trim((string) ($input['shipping_country'] ?? 'FR'))),
        ];

        if ($billingSame) {
            $billing = [
                'billing_name'             => $shipping['shipping_name'],
                'billing_line1'            => $shipping['shipping_line1'],
                'billing_postal'             => $shipping['shipping_postal'],
                'billing_city'             => $shipping['shipping_city'],
                'billing_country'          => $shipping['shipping_country'],
                'billing_same_as_shipping' => '1',
            ];
        } else {
            $billing = [
                'billing_name'             => trim((string) ($input['billing_name'] ?? '')),
                'billing_line1'            => trim((string) ($input['billing_line1'] ?? '')),
                'billing_postal'             => trim((string) ($input['billing_postal'] ?? '')),
                'billing_city'             => trim((string) ($input['billing_city'] ?? '')),
                'billing_country'          => strtoupper(trim((string) ($input['billing_country'] ?? 'FR'))),
                'billing_same_as_shipping' => '0',
            ];
        }

        $errors = $this->validateBlock($shipping, 'shipping');
        if (!$billingSame) {
            $errors = array_merge($errors, $this->validateBlock($billing, 'billing'));
        }

        return [
            'shipping'     => $shipping,
            'billing'      => $billing,
            'billing_same' => $billingSame,
            'errors'       => $errors,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function defaultFormValues(int $userId, string $fallbackName): array
    {
        $user = $this->userDao->findById($userId);
        /** @var User|null $user */
        if ($user === null) {
            return [
                'shipping_name'            => $fallbackName,
                'shipping_line1'           => '',
                'shipping_postal'            => '',
                'shipping_city'            => '',
                'shipping_country'         => 'FR',
                'billing_same_as_shipping' => '1',
                'billing_name'             => '',
                'billing_line1'            => '',
                'billing_postal'             => '',
                'billing_city'             => '',
                'billing_country'          => 'FR',
            ];
        }

        return array_merge($user->shippingAddressArray(), $user->billingAddressArray(), [
            'billing_same_as_shipping' => $user->billingSameAsShipping() ? '1' : '0',
        ]);
    }

    /**
     * @param array{
     *   shipping_name: string,
     *   shipping_line1: string,
     *   shipping_postal: string,
     *   shipping_city: string,
     *   shipping_country: string
     * } $shipping
     * @param array<string, mixed> $billingSession
     * @return array{
     *   billing_name: string,
     *   billing_line1: string,
     *   billing_postal: string,
     *   billing_city: string,
     *   billing_country: string
     * }
     */
    public function billingForOrder(array $shipping, array $billingSession): array
    {
        $same = ((string) ($billingSession['billing_same_as_shipping'] ?? '1')) === '1';

        if ($same || trim((string) ($billingSession['billing_line1'] ?? '')) === '') {
            return [
                'billing_name'    => $shipping['shipping_name'],
                'billing_line1'   => $shipping['shipping_line1'],
                'billing_postal'    => $shipping['shipping_postal'],
                'billing_city'    => $shipping['shipping_city'],
                'billing_country' => $shipping['shipping_country'],
            ];
        }

        return [
            'billing_name'    => (string) ($billingSession['billing_name'] ?? ''),
            'billing_line1'   => (string) ($billingSession['billing_line1'] ?? ''),
            'billing_postal'    => (string) ($billingSession['billing_postal'] ?? ''),
            'billing_city'    => (string) ($billingSession['billing_city'] ?? ''),
            'billing_country' => (string) ($billingSession['billing_country'] ?? 'FR'),
        ];
    }

    /**
     * @param array<string, string> $shipping
     * @param array<string, string> $billing
     */
    public function addressesEqual(array $shipping, array $billing): bool
    {
        return ($shipping['shipping_name'] ?? '') === ($billing['billing_name'] ?? '')
            && ($shipping['shipping_line1'] ?? '') === ($billing['billing_line1'] ?? '')
            && ($shipping['shipping_postal'] ?? '') === ($billing['billing_postal'] ?? '')
            && ($shipping['shipping_city'] ?? '') === ($billing['billing_city'] ?? '')
            && ($shipping['shipping_country'] ?? '') === ($billing['billing_country'] ?? '');
    }

    /**
     * @param array{
     *   shipping_name: string,
     *   shipping_line1: string,
     *   shipping_postal: string,
     *   shipping_city: string,
     *   shipping_country: string
     * } $shipping
     * @param array{
     *   billing_name: string,
     *   billing_line1: string,
     *   billing_postal: string,
     *   billing_city: string,
     *   billing_country: string
     * } $billing
     * @return array<string, mixed>
     */
    public function profilePayload(array $shipping, array $billing, bool $billingSame): array
    {
        return [
            'shipping_name'            => $shipping['shipping_name'],
            'shipping_line1'           => $shipping['shipping_line1'],
            'shipping_postal'            => $shipping['shipping_postal'],
            'shipping_city'            => $shipping['shipping_city'],
            'shipping_country'         => $shipping['shipping_country'],
            'billing_same_as_shipping' => $billingSame ? 1 : 0,
            'billing_name'             => $billing['billing_name'],
            'billing_line1'            => $billing['billing_line1'],
            'billing_postal'             => $billing['billing_postal'],
            'billing_city'             => $billing['billing_city'],
            'billing_country'          => $billing['billing_country'],
        ];
    }

    /**
     * @param array<string, string> $data
     * @return array<string, list<string>>
     */
    public function validateBlock(array $data, string $prefix): array
    {
        $nameKey    = $prefix . '_name';
        $lineKey    = $prefix . '_line1';
        $postalKey   = $prefix . '_postal';
        $cityKey    = $prefix . '_city';
        $countryKey = $prefix . '_country';

        $v = Validator::make([
            $nameKey    => $data[$nameKey] ?? '',
            $lineKey    => $data[$lineKey] ?? '',
            $postalKey   => $data[$postalKey] ?? '',
            $cityKey    => $data[$cityKey] ?? '',
            $countryKey => $data[$countryKey] ?? '',
        ], [
            $nameKey    => 'required|min:2|max:150',
            $lineKey    => 'required|min:3|max:255',
            $postalKey   => 'required',
            $cityKey    => 'required|min:2|max:100',
            $countryKey => 'required|min:2|max:2',
        ]);

        $errors = $v->errors();
        $postal = (string) ($data[$postalKey] ?? '');
        $postalLen = mb_strlen($postal);
        if ($postal !== '' && ($postalLen < 3 || $postalLen > 20)) {
            $errors[$postalKey] = ['Le code postal doit contenir entre 3 et 20 caractères.'];
        }

        return $errors;
    }
}
