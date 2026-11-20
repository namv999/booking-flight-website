<?php

namespace App\Http\Requests\Admin;

class UpdateArticleRequest extends StoreArticleRequest
{
    public function rules(): array
    {
        return parent::rules() + ['remove_thumbnail' => ['nullable', 'boolean']];
    }
}
