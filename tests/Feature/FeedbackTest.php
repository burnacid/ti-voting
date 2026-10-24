<?php

use App\Livewire\Admin\Feedback as AdminFeedback;
use App\Livewire\FeedbackForm;
use App\Models\Feedback;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.feedback' => true]);
    RateLimiter::clear('feedback|127.0.0.1');
});

test('feedback is off unless enabled, as on self-hosted installs', function () {
    config(['app.feedback' => false]);
    Feedback::create(['type' => 'idea', 'message' => 'Hidden feedback item']);

    $this->get(route('home'))->assertOk()->assertDontSee('Send feedback');
    Livewire::test(FeedbackForm::class)->assertStatus(404);

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Recent feedback')
        ->assertDontSee(route('admin.feedback'));
    $this->get(route('admin.feedback'))->assertNotFound();
});

test('the footer has a feedback link on player pages but not in the admin panel', function () {
    $this->get(route('home'))->assertOk()->assertSee('Send feedback');

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Send feedback');
});

test('feedback is saved with its type, message and name', function () {
    Livewire::test(FeedbackForm::class)
        ->set('type', 'bug')
        ->set('message', '  The vote button does not work on my phone.  ')
        ->set('name', 'Alice')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSet('message', '');

    $feedback = Feedback::sole();
    expect($feedback->type)->toBe('bug')
        ->and($feedback->message)->toBe('The vote button does not work on my phone.')
        ->and($feedback->name)->toBe('Alice')
        ->and($feedback->read_at)->toBeNull();
});

test('feedback sent from a game page remembers the game', function () {
    $game = Game::create(['code' => Game::generateCode(), 'name' => 'Feedback Game', 'status' => 'waiting']);

    // The join page has the game code in its route, like the game page
    $this->get(route('join-group', strtolower($game->code)))
        ->assertOk()
        ->assertSee('&quot;game&quot;:&quot;' . $game->code . '&quot;', false);
});

test('feedback needs a message of a sensible length and a known type', function () {
    Livewire::test(FeedbackForm::class)
        ->set('message', '')
        ->call('submit')
        ->assertHasErrors(['message' => 'required']);

    Livewire::test(FeedbackForm::class)
        ->set('message', str_repeat('a', FeedbackForm::MAX_LENGTH + 1))
        ->call('submit')
        ->assertHasErrors(['message' => 'max']);

    Livewire::test(FeedbackForm::class)
        ->set('type', 'spam')
        ->set('message', 'A perfectly fine message')
        ->call('submit')
        ->assertHasErrors(['type']);

    expect(Feedback::count())->toBe(0);
});

test('feedback from bots that fill in the hidden field is not saved', function () {
    Livewire::test(FeedbackForm::class)
        ->set('message', 'Buy cheap things here')
        ->set('website', 'https://spam.example')
        ->call('submit')
        ->assertSet('sent', true);

    expect(Feedback::count())->toBe(0);
});

test('feedback is limited to five an hour', function () {
    foreach (range(1, 5) as $i) {
        Livewire::test(FeedbackForm::class)
            ->set('message', "Feedback number {$i}")
            ->call('submit')
            ->assertHasNoErrors();
    }

    Livewire::test(FeedbackForm::class)
        ->set('message', 'One more piece of feedback')
        ->call('submit')
        ->assertHasErrors(['message']);

    expect(Feedback::count())->toBe(5);
});

test('an admin can mark feedback read and unread and delete it', function () {
    $this->actingAs(User::factory()->create());
    $first = Feedback::create(['type' => 'idea', 'message' => 'Add a timer for voting']);
    $second = Feedback::create(['type' => 'bug', 'message' => 'The map does not load']);

    $component = Livewire::test(AdminFeedback::class)
        ->assertSee('Add a timer for voting')
        ->assertSee('The map does not load')
        ->call('markRead', $first->id);

    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($second->fresh()->read_at)->toBeNull();

    $component->set('status', 'unread')
        ->assertDontSee('Add a timer for voting')
        ->assertSee('The map does not load');

    $component->call('markUnread', $first->id);
    expect($first->fresh()->read_at)->toBeNull();

    $component->call('markAllRead');
    expect(Feedback::unread()->count())->toBe(0);

    $component->call('delete', $second->id);
    expect(Feedback::find($second->id))->toBeNull();
});

test('the admin dashboard shows recent feedback', function () {
    $this->actingAs(User::factory()->create());
    Feedback::create(['type' => 'idea', 'message' => 'Show a history of past agendas']);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Recent feedback')
        ->assertSee('Show a history of past agendas')
        ->assertSee('1 unread');

    $this->get(route('admin.feedback'))->assertOk()->assertSee('Show a history of past agendas');
});
