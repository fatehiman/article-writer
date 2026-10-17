<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateArticle;
use App\Models\Article;
use App\Models\Delivery;
use App\Publishing\DeliveryDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $articles = Article::query()->where('user_id', $user->id)->with(['service', 'images'])
            ->when($request->integer('service'), fn ($q, $id) => $q->where('service_id', $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(trim((string) $request->input('q')), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$s.'%')->orWhere('source_title', 'like', '%'.$s.'%')))
            ->latest('id')->paginate(20)->withQueryString();
        $services = $user->services()->orderBy('name')->pluck('name', 'id');

        return view('articles.index', compact('articles', 'services'));
    }

    public function show(Article $article): View
    {
        $this->own($article);
        $article->load(['service', 'images', 'deliveries.destination', 'textAgent', 'imageAgent']);

        return view('articles.show', compact('article'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->own($article);
        foreach ($article->images as $img) {
            if ($img->path) {
                Storage::disk('local')->delete($img->path);
            }
        }
        $article->delete();

        return redirect()->route('articles.index')->with('ok', __('ui.deleted'));
    }

    /** Try a failed article again (same title). */
    public function retry(Article $article): RedirectResponse
    {
        $this->own($article);
        if ($article->status !== Article::FAILED || ! $article->source_title || ! $article->service) {
            return back()->withErrors(__('ui.cannot_retry'));
        }
        $article->update(['status' => Article::PENDING, 'error' => null]);
        GenerateArticle::dispatch($article->id);

        return back()->with('ok', __('ui.generation_started'));
    }

    public function resend(Delivery $delivery, DeliveryDispatcher $dispatcher): RedirectResponse
    {
        $this->own($delivery->article);
        if ($delivery->status === Delivery::SENDING) {
            return back();
        }
        $dispatcher->resend($delivery);

        return back()->with('ok', __('ui.resend_queued'));
    }
}
