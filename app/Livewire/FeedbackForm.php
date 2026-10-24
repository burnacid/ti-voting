<?php

namespace App\Livewire;

use App\Models\Feedback;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Feedback form behind the link in the footer. Submissions are listed in the admin panel.
 */
class FeedbackForm extends Component
{
    public const MAX_LENGTH = 2000;

    public $type = 'idea';
    public $message = '';
    public $name = '';
    // Hidden field that people leave empty and bots fill in
    public $website = '';
    public bool $sent = false;

    // Livewire updates go to its own endpoint, so the page is remembered when the form is first shown
    #[Locked]
    public ?string $url = null;
    #[Locked]
    public ?string $game = null;

    public function mount(): void
    {
        abort_unless(config('app.feedback'), 404);

        $this->url = Str::limit(url()->current(), 250, '');
        $code = request()->route('code');
        $this->game = is_string($code) ? Str::limit(strtoupper($code), 20, '') : null;
    }

    public function submit(): void
    {
        abort_unless(config('app.feedback'), 404);

        $this->validate([
            'type' => ['required', Rule::in(array_keys(Feedback::TYPES))],
            'message' => 'required|string|min:5|max:' . self::MAX_LENGTH,
            'name' => 'nullable|string|max:100',
        ], [
            'message.required' => 'Please write your feedback.',
            'message.min' => 'Please write a bit more.',
        ]);

        $throttleKey = 'feedback|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('message', 'Too much feedback sent. Try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minutes.');
            return;
        }
        RateLimiter::hit($throttleKey, 3600);

        // Bots that filled in the honeypot are thanked without saving anything
        if ($this->website === '' || $this->website === null) {
            $feedback = Feedback::create([
                'type' => $this->type,
                'message' => trim($this->message),
                'name' => trim((string) $this->name) ?: null,
                'game' => $this->game,
                'url' => $this->url,
                'user_agent' => Str::limit((string) request()->userAgent(), 250, '') ?: null,
            ]);

            Log::info('Feedback received', ['feedback' => $feedback->id, 'type' => $feedback->type]);
        }

        $this->reset(['type', 'message', 'name', 'website']);
        $this->resetValidation();
        $this->sent = true;
    }

    // Shows the form again after a send
    public function again(): void
    {
        $this->sent = false;
    }

    public function render()
    {
        return view('livewire.feedback-form', ['types' => Feedback::TYPES]);
    }
}
