<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        return view('portal.admin.cms.events.index', [
            'events' => Event::query()->latest('event_date')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('portal.admin.cms.events.form', ['event' => new Event]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('event-covers', 'public');
        }

        Event::create($data);

        return redirect()->route('admin.cms.events.index')->with('status', 'Evento cadastrado com sucesso.');
    }

    public function edit(Event $event)
    {
        return view('portal.admin.cms.events.form', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validatedData($request, $event);

        if ($request->hasFile('cover_image')) {
            if ($event->cover_image) {
                Storage::disk('public')->delete($event->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')->store('event-covers', 'public');
        }

        $event->update($data);

        return redirect()->route('admin.cms.events.index')->with('status', 'Evento atualizado com sucesso.');
    }

    public function destroy(Event $event)
    {
        if ($event->cover_image) {
            Storage::disk('public')->delete($event->cover_image);
        }

        $event->delete();

        return redirect()->route('admin.cms.events.index')->with('status', 'Evento removido com sucesso.');
    }

    private function validatedData(Request $request, ?Event $event = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'min:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'registration_url' => ['nullable', 'url', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['title'], $event);
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }

    private function uniqueSlug(string $title, ?Event $event = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (Event::where('slug', $slug)->when($event, fn ($query) => $query->whereKeyNot($event->id))->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
