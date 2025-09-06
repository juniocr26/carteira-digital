<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagamentoProcessado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transactionId;
    public $status;
    public $message;

    public function __construct($transactionId, $status, $message)
    {
        $this->transactionId = $transactionId;
        $this->status = $status;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('private-' . $this->transactionId);
    }
}
