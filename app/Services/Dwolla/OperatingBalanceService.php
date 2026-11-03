<?php

namespace App\Services\Dwolla;

use Illuminate\Support\Facades\Cache;

class OperatingBalanceService
{
    public function getOperatingBalance(): array
    {
        return Cache::remember('dwolla:operating-balance', 300, function () {
            $fundingSourceId = (string) config('services.dwolla.platform_funding_source');
            if ($fundingSourceId === '') {
                throw new \RuntimeException('DWOLLA_PLATFORM_FUNDING_SOURCE is not configured.');
            }

            $client = new DwollaClient();
            $fundingSourceResponse = $client->get("/funding-sources/{$fundingSourceId}");
            if (! $fundingSourceResponse->successful()) {
                throw new \RuntimeException('Unable to fetch Dwolla funding source: '.$fundingSourceResponse->body());
            }

            $fundingSourceData = $fundingSourceResponse->json();
            $balanceHref = data_get($fundingSourceData, '_links.balance.href');

            if (! is_string($balanceHref) || $balanceHref === '') {
                $accountPath = $this->pathFromHref(data_get($fundingSourceData, '_links.account.href'));

                if ($accountPath !== null) {
                    $accountFundingSourcesResponse = $client->get($accountPath.'/funding-sources');
                    if (! $accountFundingSourcesResponse->successful()) {
                        throw new \RuntimeException('Unable to fetch Dwolla account funding sources: '.$accountFundingSourcesResponse->body());
                    }

                    $balanceSource = collect(data_get($accountFundingSourcesResponse->json(), '_embedded.funding-sources', []))
                        ->first(fn ($source) => data_get($source, 'type') === 'balance' && ! (bool) data_get($source, 'removed', false));

                    $balanceHref = data_get($balanceSource, '_links.balance.href');
                }
            }

            if (! is_string($balanceHref) || $balanceHref === '') {
                throw new \RuntimeException('Dwolla funding source response missing balance link.');
            }

            $balancePath = $this->pathFromHref($balanceHref);
            if ($balancePath === null) {
                throw new \RuntimeException('Unable to parse Dwolla balance link.');
            }

            $balanceResponse = $client->get($balancePath);
            if (! $balanceResponse->successful()) {
                throw new \RuntimeException('Unable to fetch Dwolla balance: '.$balanceResponse->body());
            }

            $balanceData = $balanceResponse->json();
            $value = (float) (data_get($balanceData, 'balance.value')
                ?? data_get($balanceData, 'value')
                ?? 0);
            $currency = (string) (data_get($balanceData, 'balance.currency')
                ?? data_get($balanceData, 'currency')
                ?? 'USD');

            return [
                'funding_source_id' => $fundingSourceId,
                'available_balance' => round($value, 2),
                'currency' => $currency,
                'as_of' => now()->toDateTimeString(),
                'raw' => $balanceData,
            ];
        });
    }

    private function pathFromHref(mixed $href): ?string
    {
        if (! is_string($href) || $href === '') {
            return null;
        }

        $path = parse_url($href, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : null;
    }
}
