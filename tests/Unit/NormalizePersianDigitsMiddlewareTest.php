<?php

namespace Tests\Unit;

use App\Http\Middleware\NormalizePersianDigits;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class NormalizePersianDigitsMiddlewareTest extends TestCase
{
    public function test_form_and_query_input_are_normalized_recursively(): void
    {
        $request = Request::create('/listings?min_price=۱۲۳۴۵&filters%5B7%5D=۲۵۶', 'POST', [
            'price' => '۳۲۰۰۰۰۰۰',
            'attributes' => ['۱۱' => '۲۵۶'],
        ]);

        (new NormalizePersianDigits)->handle($request, function (Request $request): Response {
            $this->assertSame('32000000', $request->input('price'));
            $this->assertSame('256', $request->input('attributes.11'));
            $this->assertSame('12345', $request->query('min_price'));
            $this->assertSame('256', data_get($request->query->all(), 'filters.7'));

            return new Response;
        });
    }

    public function test_json_input_is_normalized_recursively(): void
    {
        $request = Request::create(
            '/dashboard/listings/bulk',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['bulk_price' => '۳۵۰۰۰۰۰', 'listing_ids' => ['۱', '۲']], JSON_THROW_ON_ERROR),
        );

        (new NormalizePersianDigits)->handle($request, function (Request $request): Response {
            $this->assertSame('3500000', $request->input('bulk_price'));
            $this->assertSame(['1', '2'], $request->input('listing_ids'));

            return new Response;
        });
    }
}
