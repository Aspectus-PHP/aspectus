<?php

namespace Aspectus;


interface Model
{
    /**
     * Receives a Message and updates the model accordingly.
     *
     * Receiving `null` here, by convention means that you have a last chance to initialize something. All
     * subsequent calls to update() will contain a message.
     *
     * @todo Maybe the message returned here is a command only which needs to be queued for the next iteration ?
     *      Not sure, as if we have a SKIP_RENDER one it shouldnt be..
     */
    public function update(Message $message): ?Message;

    /**
     * Returns a list of all the Message types that this model is interested in
     * All children collaborators should be asked as well and returned
     *
     * @return string[]
     */
//    public function subscribes(): array;
}
