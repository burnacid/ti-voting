<?php

namespace App\Livewire;

use App\Events\TriggerRefresh;
use App\Services\MiltyService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UpdateMiltyUrl extends Component
{
    public $game;
    public $miltyUrl = '';
    public $showModal = false;

    public function mount($game): void
    {
        $this->game = $game;
        $this->miltyUrl = $game->milty_url ?? '';
    }

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function updateMiltyUrl(): void
    {
        $this->validate([
            'miltyUrl' => 'required|url',
        ]);

        $miltyService = new MiltyService();
        $draftId = $miltyService->extractDraftId($this->miltyUrl);

        if (!$draftId) {
            $this->dispatch('flashMessage', [
                'Invalid Milty URL format. Expected format: https://milty.shenanigans.be/d/{draft_id}',
                'error'
            ]);
            return;
        }

        $draftData = $miltyService->fetchDraftData($draftId);

        if (!$draftData) {
            $this->dispatch('flashMessage', [
                'Could not fetch Milty draft data. Please check the URL and try again.',
                'error'
            ]);
            return;
        }

        $this->game->update([
            'milty_url' => $this->miltyUrl,
            'miltyDraftData' => $draftData,
            'milty_draft_id' => $draftId,
        ]);

        $this->dispatch('flashMessage', [
            "Updated Milty URL successfully.",
            'success'
        ]);

        TriggerRefresh::dispatch($this->game);

        $this->closeModal();$this->dispatch('miltyUrlUpdated');
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        return view('livewire.update-milty-url');
    }
}
