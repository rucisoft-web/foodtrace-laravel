<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pengumuman di landing page untuk peran tertentu. Hanya Administrator yang boleh mengisi. */
class AnnouncementController extends Controller
{
    public function index(Request $r)
    {
        $this->requireAdmin();

        return view('ann.index', ['rows' => Announcement::with('user')->orderByDesc('pinned')->latest('id')->get(), 'edit' => $r->edit ? Announcement::find($r->edit) : null]);
    }

    public function save(Request $r, ?Announcement $announcement = null)
    {
        $this->requireAdmin();
        $a = $announcement ?? new Announcement;
        $data = $r->validate(['title' => 'required|max:255', 'body' => 'required|max:5000', 'level' => ['required', Rule::in(array_keys(Announcement::LEVELS))],
            'roles' => 'nullable|array', 'roles.*' => Rule::in(array_keys(User::ROLES)), 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at']);
        $a->fill($data + ['roles' => $data['roles'] ?? null, 'pinned' => $r->boolean('pinned')]);
        $a->user_id ??= auth()->id();
        $a->save();

        return redirect()->route('ann.index')->with('ok', "Pengumuman \"{$a->title}\" disimpan dan tampil di halaman depan.");
    }

    public function destroy(Announcement $announcement)
    {
        $this->requireAdmin();
        $announcement->delete();

        return back()->with('ok', 'Pengumuman dihapus.');
    }
}
