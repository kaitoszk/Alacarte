<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DraftController extends Controller
{
    /**
     * 自分の下書きの一覧。最後に手を入れたものから並べる。
     */
    public function index(Request $request): Response
    {
        $drafts = $request->user()->recipes()
            ->draft()
            ->latest('updated_at')
            ->latest('id')
            ->paginate(20);

        return Inertia::render('drafts/Index', [
            'drafts' => RecipeResource::collection($drafts),
        ]);
    }
}
