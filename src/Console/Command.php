<?php

namespace SilexStarter\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Command extends SymfonyCommand
{
    public function getSilexStarter()
    {
        return $this->getApplication()->getSilexStarter();
    }
}
