<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\UpdateListingRequest as BaseUpdateListingRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends BaseUpdateListingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return parent::rules() + [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'sold', 'expired'])],
        ];
    }
}
