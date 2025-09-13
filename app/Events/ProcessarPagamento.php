<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessarPagamento
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $mensagem;
    public string $idTransaction;
    public string $status;

    public function __construct(string $mensagem, string $idTransaction, string $status)
    {
        $this->mensagem = $mensagem;
        $this->idTransaction = $idTransaction;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return new PrivateChannel('transacao-'.$this->idTransaction);
    }


    public function broadcastAs(): string
    {
        return 'transacao-'.$this->idTransaction;
    }
}
