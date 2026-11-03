<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Finds existing client (Bill To) records that may be duplicates of a new entry.
 * Searches all clients (active and inactive).
 */
class SimilarBillingClientMatcher
{
    /**
     * @return Collection<int, array{client: User, score: float, reasons: array<int, string>}>
     */
    public function findSimilar(
        ?string $firstName,
        ?string $lastName,
        ?string $email,
        ?string $phone,
        ?string $address,
        ?int $excludeUserId = null,
        int $limit = 20
    ): Collection {
        $first = trim((string) $firstName);
        $last = trim((string) $lastName);
        $full = strtolower(preg_replace('/\s+/u', ' ', $first.' '.$last));
        $emailNorm = strtolower(trim((string) $email));
        $phoneDigits = preg_replace('/\D/', '', (string) $phone);
        $addressNorm = strtolower(preg_replace('/\s+/u', ' ', trim((string) $address)));

        $query = User::query()
            ->where('role_id', 3)
            ->with('clientProfile:id,user_id,quickbooks_customer_id')
            ->select([
                'id', 'first_name', 'last_name', 'name', 'email', 'phone',
                'status',
            ]);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $candidates = $query->get();

        $scored = [];

        foreach ($candidates as $client) {
            $reasons = [];
            $score = 0.0;

            $cFull = strtolower(preg_replace('/\s+/u', ' ', trim(
                trim((string) $client->first_name).' '.trim((string) $client->last_name)
            )));
            if ($cFull === '') {
                $cFull = strtolower(preg_replace('/\s+/u', ' ', trim((string) $client->name)));
            }

            if ($full !== '' && $cFull !== '') {
                similar_text($full, $cFull, $namePct);
                if ($namePct >= 55) {
                    $score += min((float) $namePct, 100);
                    $reasons[] = 'Name ~'.round($namePct).'% match';
                }
            }

            // Word overlap on name (catches "Acme Corp" vs "Acme Corporation")
            if ($full !== '' && $cFull !== '') {
                $words = array_filter(preg_split('/\s+/u', $full) ?: [], fn ($w) => strlen($w) > 2);
                $cWords = array_flip(preg_split('/\s+/u', $cFull) ?: []);
                $overlap = 0;
                foreach ($words as $w) {
                    if (isset($cWords[$w])) {
                        $overlap++;
                    }
                }
                if (count($words) > 0 && $overlap / count($words) >= 0.5) {
                    $score += 25;
                    $reasons[] = 'Similar name words';
                }
            }

            if (strlen($phoneDigits) >= 7) {
                $cPhone = preg_replace('/\D/', '', (string) $client->phone);
                if ($cPhone !== '' && $cPhone === $phoneDigits) {
                    $score += 45;
                    $reasons[] = 'Same phone number';
                }
            }

            if ($emailNorm !== '') {
                $cEmail = strtolower(trim((string) $client->email));
                if ($cEmail === $emailNorm) {
                    $score += 100;
                    $reasons[] = 'Same email';
                }
            }

            if (strlen($addressNorm) > 10 && strlen(trim((string) $client->address)) > 10) {
                $cAddr = strtolower(preg_replace('/\s+/u', ' ', trim((string) $client->address)));
                similar_text($addressNorm, $cAddr, $addrPct);
                if ($addrPct >= 72) {
                    $score += min((float) $addrPct * 0.35, 35);
                    $reasons[] = 'Address ~'.round($addrPct).'% match';
                }
            }

            if ($score >= 48 && count($reasons) > 0) {
                $scored[] = [
                    'client' => $client,
                    'score' => $score,
                    'reasons' => array_values(array_unique($reasons)),
                ];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return collect(array_slice($scored, 0, $limit));
    }
}
