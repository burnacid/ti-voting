<?php

namespace App\Livewire;

use Livewire\Component;

class FlashMessages extends Component
{
    public $messages = [];

    protected $listeners = ['flashMessage' => 'addMessage'];

    public function mount()
    {
        if (session()->has('flash_message')) {
            $this->addMessage(
                session('flash_message'),
                session('flash_type', 'info')
            );
            session()->forget(['flash_message', 'flash_type']);
        }
    }

    // Update this method to handle both array and string inputs
    public function addMessage($message, $type = null)
    {
        // If $message is an array and $type is null, extract message and type from array
        if (is_array($message) && $type === null) {
            $type = $message[1] ?? 'info';
            $message = $message[0] ?? '';
        }

        // Ensure $message is a string
        $message = (string) $message;

        $this->messages[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type
        ];

        $this->dispatch('newFlashMessage');
    }

    public function removeMessage($id)
    {
        $this->messages = array_filter($this->messages, function($message) use ($id) {
            return $message['id'] !== $id;
        });
    }

    public function getTypeClasses($type)
    {
        return match ($type) {
            'success' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
            'error' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
            default => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
        };
    }

    public function render()
    {
        return view('livewire.flash-messages');
    }
}
