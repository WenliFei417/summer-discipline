<?php

namespace App\Http\Requests;

class UpdateRecordRequest extends StoreRecordRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['record_date'] = ['sometimes', 'date_format:Y-m-d'];

        return $rules;
    }
}
