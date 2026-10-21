<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

abstract class Controller
{
    /**
     * Voucher files sent with a form. Validation already requires an array,
     * this only gives the services the list they expect.
     *
     * @return list<UploadedFile>
     */
    protected function attachments(Request $request): array
    {
        return array_values(Arr::wrap($request->file('attachments', [])));
    }
}
