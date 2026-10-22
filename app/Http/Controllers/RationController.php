<?php

namespace App\Http\Controllers;

use App\Services\RationBook;
use Inertia\Inertia;
use Inertia\Response;

/** খাবারের রেশন হিসাব: a rule-of-thumb daily ration for each group of cattle and what it costs. */
class RationController extends Controller
{
    public function index(RationBook $book): Response
    {
        return Inertia::render('ration/Index', $book->page());
    }
}
