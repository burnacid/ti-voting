<?php

namespace App\Livewire\Admin;

use App\Models\Feedback as FeedbackModel;
use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Feedback extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public $type = '';

    // '' for all feedback, 'unread' for unread only
    #[Url(except: '')]
    public $status = '';

    public function boot()
    {
        abort_unless(config('app.feedback'), 404);
    }

    public function updated($property)
    {
        if (in_array($property, ['type', 'status'])) {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset(['type', 'status']);
        $this->resetPage();
    }

    public function markRead($id)
    {
        FeedbackModel::whereKey($id)->unread()->update(['read_at' => now()]);
    }

    public function markUnread($id)
    {
        FeedbackModel::whereKey($id)->update(['read_at' => null]);
    }

    public function markAllRead()
    {
        $marked = FeedbackModel::unread()->update(['read_at' => now()]);

        Log::info('Admin marked all feedback as read', ['feedback' => $marked, 'admin' => Auth::user()->email]);
    }

    public function delete($id)
    {
        $feedback = FeedbackModel::find($id);
        if (!$feedback) {
            return;
        }

        $feedback->delete();

        Log::info('Admin deleted feedback', ['feedback' => $feedback->id, 'admin' => Auth::user()->email]);
    }

    private function filtered(): Builder
    {
        return FeedbackModel::query()
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->when($this->status === 'unread', fn ($query) => $query->unread());
    }

    public function render()
    {
        $items = $this->filtered()->latest('id')->paginate(25);
        $games = $items->pluck('game')->filter()->unique();

        return view('livewire.admin.feedback', [
            'items' => $items,
            'types' => FeedbackModel::TYPES,
            'typeCounts' => FeedbackModel::query()->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type'),
            'unreadCount' => FeedbackModel::unread()->count(),
            // Games that still exist link to their admin page
            'existingGames' => Game::whereIn('code', $games)->pluck('id', 'code'),
        ]);
    }
}
