<?php

namespace App\Http\Controllers;

use App\Http\Requests\TendererProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class TendererProfileController extends Controller
{
    public function update(TendererProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()
            ->tendererProfile()
            ->updateOrCreate([], $request->validated());

        return Redirect::route('profile.edit');
    }
}
