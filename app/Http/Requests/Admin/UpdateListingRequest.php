<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\UpdateListingRequest as BaseUpdateListingRequest;

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
        ];
    }
}
